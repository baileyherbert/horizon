<?php

namespace Horizon\Database\Migration;

use Database;
use Exception;

/**
 * Utility class for building, managing, and testing database schemas.
 */
class Schema {

	/**
	 * @var SchemaConnection|null
	 */
	protected static $connection;

	/**
	 * @var SchemaConnection[]
	 */
	protected static $connectionCache = array();

	/**
	 * Returns a schema instance for the given connection.
	 *
	 * @param string|null $name
	 * @return SchemaConnection
	 */
	public static function connection($name = null) {
		if ($name === null) {
			if (static::$connection === null) {
				static::$connection = static::connection('main');
			}

			return static::$connection;
		}

		if (array_key_exists($name, static::$connectionCache)) {
			$cached = static::$connectionCache[$name];

			return $cached;
		}

		$cached = static::$connectionCache[$name] = new SchemaConnection(Database::connection($name));

		return $cached;
	}

	/**
	 * Executes the given callable with the default connection set to the given connection name.
	 *
	 * @param string $connectionName
	 * @param callable $callable
	 * @return void
	 */
	public static function withDefaultConnection($connectionName, $callable) {
		$previous = static::$connection;
		static::$connection = static::connection($connectionName);

		try {
			$callable();
		}
		catch (Exception $ex) {
			static::$connection = $previous;
			throw $ex;
		}

		static::$connection = $previous;
	}

	/**
	 * Spawns a table blueprint for creating a new table schema. The callable will be passed the Blueprint instance.
	 *
	 * @param string $name
	 * @param callable $callable
	 * @return bool
	 */
	public static function create($name, $callable) {
		static::connection()->create($name, $callable);
	}

	/**
	 * Spawns a table blueprint for modifying an existing table schema. The callable will be passed the Blueprint
	 * instance.
	 *
	 * @param string $name
	 * @param callable $callable
	 * @return bool
	 */
	public static function table($name, $callable) {
		return static::connection()->table($name, $callable);
	}

	/**
	 * Renames a table if it exists.
	 *
	 * @param string $from Current table name.
	 * @param string $to New table name.
	 * @return bool
	 */
	public static function rename($from, $to) {
		return static::connection()->rename($from, $to);
	}

	/**
	 * Truncates a table.
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function truncate($name) {
		return static::connection()->truncate($name);
	}

	/**
	 * Drops a table. Will error if the table does not exist.
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function drop($name) {
		return static::connection()->drop($name);
	}

	/**
	 * Checks if the table exists and drops it.
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function dropIfExists($name) {
		return static::connection()->dropIfExists($name);
	}

	/**
	 * Gets the current prefix or prepends it to the given table name.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function prefix($name = null) {
		return static::connection()->prefix($name);
	}

	/**
	 * Checks if the table exists in the database.
	 *
	 * @param string $tableName
	 * @return bool
	 */
	public static function hasTable($tableName) {
		return static::connection()->hasTable($tableName);
	}

	/**
	 * Checks if the column exists in the database.
	 *
	 * @param string $tableName
	 * @param string $columnName
	 * @return bool
	 */
	public static function hasColumn($tableName, $columnName) {
		return static::connection()->hasColumn($tableName, $columnName);
	}

}
