<?php

namespace Horizon\Database\Drivers;

use Horizon\Database\Database;
use Horizon\Database\Exception\DatabaseDriverException;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Support\Profiler;
use Horizon\Support\Str;

use PDO;
use PDOException;

class PdoDriver implements DriverInterface {

	/**
	 * @var Database
	 */
	protected $database;

	/**
	 * @var bool
	 */
	protected $connected = false;

	/**
	 * @var PDO
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

		Profiler::start('database:connect', 'pdo');

		try {
			$config = $this->database->getConfig();
			$handle = new PDO(
				sprintf(
					'mysql:host=%s;dbname=%s;charset=%s',
					$config['host'],
					$config['database'],
					$config['charset']
				),
				$config['username'],
				$config['password']
			);

			$handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e) {
			$message = $e->getMessage();

			while ($pos = strpos($message, '] ')) {
				$message = trim(substr($message, $pos + 1));
			}

			throw new DatabaseDriverException(sprintf('Failed to connect to database: %s', $message), $e->getCode());
		}

		// Save the handle and status
		$this->handle = $handle;
		$this->connected = true;

		// Set the charset and collation
		$this->handle->exec(sprintf('SET NAMES %s COLLATE %s;', $config['charset'], $config['collation']));

		// Set the timezone
		if (config('database.send_timezone', true)) {
			$this->database->setTimezone();
		}

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
	public function query($statement, $bindings = null) {
		$this->connect();

		$statement = trim($statement);

		if (is_array($bindings) && !empty($bindings)) {
			return $this->prepared($statement, $bindings);
		}

		try {
			$type = $this->getQueryType($statement);
			$query = $this->handle->$type($statement);
		}
		catch (PDOException $e) {
			throw new DatabaseException(sprintf('Query error: %s', $this->handle->errorInfo()[2]));
		}

		if (!is_object($query) && is_numeric($query)) {
			if (Str::startsWith(strtolower($statement), 'insert into')) return $this->handle->lastInsertId();
			if (Str::startsWith(strtolower($statement), 'create ')) return true;
			if (Str::startsWith(strtolower($statement), 'alter ')) return true;

			return $query;
		}

		return $query->fetchAll(PDO::FETCH_OBJ);
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
		$type = $this->getQueryType($statement);

		try {
			$p = $this->handle->prepare($statement);
		} catch (PDOException $e) {
			throw new DatabaseException(sprintf('Prepared statement failed: %s', $this->handle->errorInfo()[2]));
		}

		try {
			$p->execute($bindings);
		} catch (PDOException $e) {
			throw new DatabaseException(sprintf('Prepared statement errored: %s', $p->errorInfo()[2]));
		}

		if ($type == 'query') {
			return $p->fetchAll(PDO::FETCH_OBJ);
		}
		else {
			if (Str::startsWith(strtolower($statement), 'insert into')) return $this->handle->lastInsertId();
			if (Str::startsWith(strtolower($statement), 'create ')) return true;
			if (Str::startsWith(strtolower($statement), 'alter ')) return true;

			return $p->rowCount();
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
	public function validate($statement) {
		try {
			$this->handle->prepare($statement);
		}
		catch (PDOException $e) {
			throw new DatabaseException($this->handle->errorInfo()[2]);
		}
	}

	/**
	 * Gets whether the query should be ran with query() or exec().
	 *
	 * @param string $statement
	 * @return string
	 */
	protected function getQueryType($statement) {
		$statement = strtolower($statement);

		if (Str::startsWith($statement, 'select ')) return 'query';
		if (Str::startsWith($statement, 'show ')) return 'query';

		return 'exec';
	}

	/**
	 * Checks if the server supports this driver.
	 *
	 * @return bool
	 */
	public static function supported() {
		return (class_exists('PDO') && defined('PDO::MYSQL_ATTR_LOCAL_INFILE'));
	}

	/**
	 * Closes the connection.
	 */
	public function close() {
		$this->handle = null;
	}

}
