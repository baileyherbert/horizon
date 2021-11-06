<?php

namespace Horizon\Database;

use Horizon\Database\Exception\DatabaseDriverException;
use Horizon\Database\Exception\MigrationException;
use Horizon\Foundation\Kernel;

use Horizon\Database\QueryBuilder\Documentation\AlterHelper;
use Horizon\Database\QueryBuilder\Documentation\CreateHelper;
use Horizon\Database\QueryBuilder\Documentation\DeleteHelper;
use Horizon\Database\QueryBuilder\Documentation\DropHelper;
use Horizon\Database\QueryBuilder\Documentation\InsertHelper;
use Horizon\Database\QueryBuilder\Documentation\SelectHelper;
use Horizon\Database\QueryBuilder\Documentation\ShowHelper;
use Horizon\Database\QueryBuilder\Documentation\UpdateHelper;
use Horizon\Database\Exception\DatabaseException;

class DatabaseFacade
{

	/**
	 * @var DatabaseConnection[]
	 */
	protected static $connections = array();

	/**
	 * Selects which database connection to use.
	 *
	 * @param string $name
	 * @return DatabaseConnection
	 */
	public static function connection($name = null)
	{
		if (isset(static::$connections[$name])) {
			return static::$connections[$name];
		}

		static::$connections[$name] = new DatabaseConnection($name);

		return static::$connections[$name];
	}

	/**
	 * Executes a query. The return value of this method depends on the type of query:
	 *
	 *  ... SELECT, SHOW queries return an associative array of row results.
	 *  ... UPDATE, DELETE, DROP queries return number of affected rows.
	 *  ... INSERT queries return the inserted row ID.
	 *  ... ALTER, CREATE queries return true (boolean).
	 *
	 * All queries will throw a DatabaseException if they produce an error.
	 *
	 * Prepared statements are used if the active driver supports them. Otherwise, traditional real escaping is used.
	 * Use the '?' character for prepared statements, passing values into the second parameter as an array.
	 *
	 * @param string $statement
	 * @param array $bindings
	 * @return array|int|bool
	 */
	public static function query($statement, array $bindings = array())
	{
		return static::connection()->query($statement, $bindings);
	}

	/**
	 * Creates a query builder with the ALTER command.
	 *
	 * @return AlterHelper
	 */
	public static function alter()
	{
		return static::connection()->alter();
	}

	/**
	 * Creates a query builder with the CREATE command.
	 *
	 * @return CreateHelper
	 */
	public static function create()
	{
		return static::connection()->create();
	}

	/**
	 * Creates a query builder with the DELETE command.
	 *
	 * @return DeleteHelper
	 */
	public static function delete()
	{
		return static::connection()->delete();
	}

	/**
	 * Creates a query builder with the DROP command.
	 *
	 * @return DropHelper
	 */
	public static function drop()
	{
		return static::connection()->drop();
	}

	/**
	 * Creates a query builder with the INSERT command.
	 *
	 * @return InsertHelper
	 */
	public static function insert()
	{
		return static::connection()->insert();
	}

	/**
	 * Creates a query builder with the SELECT command.
	 *
	 * @return SelectHelper
	 */
	public static function select()
	{
		return static::connection()->select();
	}

	/**
	 * Creates a query builder with the SHOW command.
	 *
	 * @return ShowHelper
	 */
	public static function show()
	{
		return static::connection()->show();
	}

	/**
	 * Creates a query builder with the UPDATE command.
	 *
	 * @return UpdateHelper
	 */
	public static function update()
	{
		return static::connection()->update();
	}

	/**
	 * Gets the database instance from the kernel. Note that the kernel will create the instance if it hasn't been
	 * loaded already.
	 *
	 * @return Database
	 */
	public static function getDatabase()
	{
		return static::connection()->getDatabase();
	}

	/**
	 * Starts a transaction.
	 *
	 * @return bool
	 */
	public static function transaction()
	{
		try {
			static::query('START TRANSACTION;');
			return true;
		}
		catch (DatabaseException $e) {
			return false;
		}
	}

	/**
	 * Commits the current transaction.
	 *
	 * @return bool
	 */
	public static function commit()
	{
		try {
			static::query('COMMIT;');
			return true;
		}
		catch (DatabaseException $e) {
			return false;
		}
	}

	/**
	 * Rolls back the current transaction.
	 *
	 * @return bool
	 */
	public static function rollback()
	{
		try {
			static::query('ROLLBACK;');
			return true;
		}
		catch (DatabaseException $e) {
			return false;
		}
	}

}
