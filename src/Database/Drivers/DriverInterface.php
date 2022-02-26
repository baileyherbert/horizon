<?php

namespace Horizon\Database\Drivers;

use Horizon\Database\Database;
use Horizon\Database\Exception\DatabaseException;

interface DriverInterface {

	/**
	 * Creates a new driver instance.
	 *
	 * @param Database $database
	 */
	public function __construct(Database $database);

	/**
	 * Connects to the database server.
	 *
	 * @throws DatabaseException on error
	 */
	public function connect();

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
	public function query($statement, $bindings = null, $rowFunction = null);

	/**
	 * Validates a query using prepared statements and throws an exception upon invalid syntax. This is ignored on
	 * unsupported drivers or platforms.
	 *
	 * @param string $statement
	 * @return void
	 *
	 * @throws DatabaseException on error
	 */
	public function validate($statement);

	/**
	 * Checks if the server supports this driver.
	 *
	 * @return bool
	 */
	public static function supported();

	/**
	 * Closes the connection.
	 */
	public function close();

}
