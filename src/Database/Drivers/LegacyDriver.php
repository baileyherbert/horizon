<?php

namespace Horizon\Database\Drivers;

use Exception;
use Horizon\Database\Database;
use Horizon\Database\Exception\DatabaseDriverException;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Support\Str;

class LegacyDriver implements DriverInterface {

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
	public function __construct(Database $database) {
		$this->database = $database;
	}

	/**
	 * Connects to the database server.
	 *
	 * @throws DatabaseDriverException on error
	 */
	public function connect() {
		if ($this->connected) {
			return;
		}

		$config = $this->database->getConfig();
		$port = isset($config['port']) ? $config['port'] : 3306;
		$host = $config['host'] . ':' . $port;
		$handle = @mysql_connect($host, $config['username'], $config['password']);

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

		if (config('database.send_timezone', true)) {
			$this->database->setTimezone();
		}
	}

	/**
	 * Executes a query on the database server and returns the results.
	 *
	 * @param string $statement
	 * @param array|null $bindings
	 * @param callable|null $rowFunction
	 * @return int|object|bool
	 *
	 * @throws DatabaseException on error
	 */
	public function query($statement, $bindings = null, $rowFunction = null) {
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
		$numRows = 0;

		while ($row = mysql_fetch_object($query)) {
			if ($rowFunction) {
				$rowFunction($row);
				$numRows++;
			}
			else {
				$rows[] = $row;
			}
		}

		return $rowFunction ? $numRows : $rows;
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
	public function validate($statement) {
		// Not supported
		return;
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
	protected function prepared($statement, array &$bindings = array()) {
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
	protected function prepareValue($value) {
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
	public static function supported() {
		return true;//(function_exists('mysql_ping'));
	}

	/**
	 * Closes the connection.
	 */
	public function close() {
		@mysql_close($this->handle);
	}

}

if (!function_exists('mysql_connect')) {
	function mysql_connect() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_error() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_errno() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_select_db() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_set_charset() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_query() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_affected_rows() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_insert_id() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_fetch_object() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_real_escape_string() {
		throw new Exception('The mysql extension is not available on this system');
	}

	function mysql_close() {
		throw new Exception('The mysql extension is not available on this system');
	}
}
