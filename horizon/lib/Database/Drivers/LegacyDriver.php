<?php

namespace Horizon\Database\Drivers;

use Horizon\Database\Database;
use Horizon\Database\Exception\DatabaseDriverException;

use mysqli;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Utils\Str;

class LegacyDriver implements DriverInterface
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
     * @var resource
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

        $config = $this->database->getConfig();
        $handle = @mysql_connect($config['host'], $config['username'], $config['password']);

        if (!$handle) {
            throw new DatabaseDriverException(sprintf('Failed to connect to database: %s', mysql_error($this->handle)), mysql_errno($this->handle));
        }

        if (!@mysql_select_db($config['database'], $handle)) {
            throw new DatabaseDriverException(
                sprintf('Failed to connect to database: Unknown database \'%s\' or no permissions',
                $config['database'])
            );
        }

        // Save the handle and status
        $this->handle = $handle;
        $this->connected = true;

        // Set the charset and collation
        @mysql_set_charset($config['charset'], $this->handle);
        @mysql_query(sprintf('SET NAMES %s COLLATE %s;', $config['charset'], $config['collation']), $this->handle);
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

        $query = @mysql_query($statement, $this->handle);

        if ($query === false) {
            throw new DatabaseException(sprintf('Query error: %s', mysql_error($this->handle)));
        }

        if ($query === true) {
            if (Str::startsWith(strtolower($statement), 'insert into')) return mysql_insert_id($this->handle);
            if (Str::startsWith(strtolower($statement), 'create ')) return true;
            if (Str::startsWith(strtolower($statement), 'alter ')) return true;

            return mysql_affected_rows($this->handle);
        }

        $rows = array();

        while ($row = mysql_fetch_object($query)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Executes a prepared statement on the database server and returns the results.
     *
     * @param string $statement
     * @param array $bindings
     * @return int|object|bool
     *
     * @throws DatabaseException on error
     */
    protected function prepared($statement, array &$bindings = array())
    {
        $marks = array();
        $replacements = array();

        foreach ($bindings as $value) {
            $marks[] = '?';
            $replacements[] = $this->prepareValue($value);
        }

        $statement = str_replace($marks, $replacements, $statement);

        try {
            return $this->query($statement);
        }
        catch (DatabaseException $e) {
            $message = $e->getMessage();
            $message = str_replace('Query error: ', '', $message);

            throw new DatabaseException(sprintf('Prepared statement failed: %s', $message, $e->getCode()));
        }
    }

    /**
     * Formats and/or escapes a value for use in a prepared statement.
     *
     * @param mixed $value
     * @return string
     */
    protected function prepareValue($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        else {
            return sprintf("'%s'", mysql_real_escape_string($value, $this->handle));
        }
    }

    /**
     * Checks if the server supports this driver.
     *
     * @return bool
     */
    public static function supported()
    {
        return (function_exists('mysql_connect'));
    }

    /**
     * Closes the connection.
     */
    public static function close()
    {
        @mysql_close($this->handle);
    }

}
