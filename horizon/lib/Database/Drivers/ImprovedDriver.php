<?php

namespace Horizon\Database\Drivers;

use Horizon\Database\Database;
use Horizon\Database\Exception\DatabaseDriverException;

use mysqli;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Support\Profiler;
use Horizon\Support\Str;

class ImprovedDriver implements DriverInterface
{

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var mysqli
     */
    protected $handle;

    /**
     * Creates a new driver instance.
     *
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Connects to the database server.
     *
     * @throws DatabaseDriverException on error
     */
    public function connect()
    {
        if ($this->connected) {
            return;
        }

        Profiler::start('database:connect', 'mysqli');
        $config = $this->database->getConfig();
        $handle = @new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

        if ($handle->connect_error) {
            throw new DatabaseDriverException(sprintf('Failed to connect to database: %s', $handle->connect_error), $handle->connect_errno);
        }

        // Save the handle and status
        $this->handle = $handle;
        $this->connected = true;

        // Set the charset and collation
        Profiler::start('database:connect:charset', $config['charset']);
        $this->handle->set_charset($config['charset']);
        $this->handle->query(sprintf('SET NAMES %s COLLATE %s;', $config['charset'], $config['collation']));
        Profiler::stop('database:connect:charset');
        Profiler::stop('database:connect');
    }

    /**
     * Executes a query on the database server and returns the results.
     *
     * @param string $statement
     * @param array $bindings
     * @return int|object|bool
     *
     * @throws DatabaseException on error
     */
    public function query($statement, $bindings = null)
    {
        $this->connect();

        $statement = trim($statement);

        if (is_array($bindings) && !empty($bindings)) {
            return $this->prepared($statement, $bindings);
        }

        $query = $this->handle->query($statement);

        if ($query === false) {
            throw new DatabaseException(sprintf('Query error: %s', $this->handle->error));
        }

        if ($query === true) {
            if (Str::startsWith(strtolower($statement), 'insert into')) return $this->handle->insert_id;
            if (Str::startsWith(strtolower($statement), 'create ')) return true;
            if (Str::startsWith(strtolower($statement), 'alter ')) return true;

            return $this->handle->affected_rows;
        }

        $rows = array();

        while ($row = $query->fetch_object()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Executes a prepared statement on the database server and returns the results.
     *
     * @param string $statement
     * @param array $bindings
     * @return int|array|bool
     *
     * @throws DatabaseException on error
     */
    protected function prepared($statement, array &$bindings = array())
    {
        if ($p = $this->handle->prepare($statement)) {
            $types = StringBuilder::generateTypes($bindings);
            $parameters = array($types);

            // Get references to the parameters
            foreach ($bindings as $key => $value) {
                $parameters[] = &$bindings[$key];
            }

            // Bind the parameters
            call_user_func_array(array($p, 'bind_param'), $parameters);

            // Execute the query
            $p->execute();

            // Handle errors
            if ($p->error) {
                throw new DatabaseException(sprintf('Prepared statement errored: %s', $p->error));
            }

            $returnValue = $this->getPreparedResults($p, $statement);
            $p->close();

            return $returnValue;
        }
        else {
            throw new DatabaseException(sprintf('Prepared statement failed: %s', $this->handle->error));
        }
    }

    /**
     * Validates a query using prepared statements and throws an exception upon invalid syntax. This is ignored on
     * unsupported drivers or platforms.
     *
     * @param string $statement
     * @return void
     *
     * @throws DatabaseException on error
     */
    public function validate($statement)
    {
        if (!$this->handle->prepare($statement)) {
            throw new DatabaseException($this->handle->error);
        }
    }

    /**
     * Gets the results of a prepared statement. That is, the number of affected rows, the inserted id, or an array of
     * rows, or true, depending on the type of query.
     *
     * @param \mysqli_stmt $p
     * @param string $statement query
     * @return int|array
     */
    protected function getPreparedResults($p, $statement)
    {
        $p->store_result();

        $array = array();
        $variables = array();
        $data = array();
        $meta = $p->result_metadata();

        if ($meta === false) {
            if (Str::startsWith(strtolower($statement), 'insert into')) return $p->insert_id;
            if (Str::startsWith(strtolower($statement), 'create ')) return true;
            if (Str::startsWith(strtolower($statement), 'alter ')) return true;

            return $p->affected_rows;
        }

        // Skip the calculation work below if there are no results
        if ($p->num_rows == 0) {
            return array();
        }

        // Prepare references to bind results
        while ($field = $meta->fetch_field()) {
            $variables[] = &$data[$field->name];
        }

        // Bind results
        call_user_func_array(array($p, 'bind_result'), $variables);

        // Fetch results
        $i=0;
        while ($p->fetch())
        {
            $tmp = array();

            foreach ($data as $k => $v) {
                $tmp[$k] = $v;
            }

            $array[$i] = (object)$tmp;

            $i++;
        }

        return $array;
    }

    /**
     * Checks if the server supports this driver.
     *
     * @return bool
     */
    public static function supported()
    {
        return (function_exists('mysqli_connect'));
    }

    /**
     * Closes the connection.
     */
    public function close()
    {
        if (!is_null($this->handle)) {
            @$this->handle->close();
        }
    }

}
