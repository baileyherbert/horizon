<?php

namespace Horizon\Database;

use Horizon\Database\Exception\DatabaseDriverException;
use Horizon\Database\Exception\MigrationException;
use Horizon\Database\Migration\Migration;
use Horizon\Foundation\Application;
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

class DatabaseConnection
{

	private $name;

	public function __construct($name)
	{
		$this->name = $name;
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
	public function query($statement, array $bindings = array())
	{
		return $this->getDatabase()->query($statement, $bindings);
	}

	/**
	 * Creates a query builder with the ALTER command.
	 *
	 * @return AlterHelper
	 */
	public function alter()
	{
		return $this->getDatabase()->createQueryBuilder('ALTER');
	}

	/**
	 * Creates a query builder with the CREATE command.
	 *
	 * @return CreateHelper
	 */
	public function create()
	{
		return $this->getDatabase()->createQueryBuilder('CREATE');
	}

	/**
	 * Creates a query builder with the DELETE command.
	 *
	 * @return DeleteHelper
	 */
	public function delete()
	{
		return $this->getDatabase()->createQueryBuilder('DELETE');
	}

	/**
	 * Creates a query builder with the DROP command.
	 *
	 * @return DropHelper
	 */
	public function drop()
	{
		return $this->getDatabase()->createQueryBuilder('DROP');
	}

	/**
	 * Creates a query builder with the INSERT command.
	 *
	 * @return InsertHelper
	 */
	public function insert()
	{
		return $this->getDatabase()->createQueryBuilder('INSERT');
	}

	/**
	 * Creates a query builder with the SELECT command.
	 *
	 * @return SelectHelper
	 */
	public function select()
	{
		return $this->getDatabase()->createQueryBuilder('SELECT');
	}

	/**
	 * Creates a query builder with the SHOW command.
	 *
	 * @return ShowHelper
	 */
	public function show()
	{
		return $this->getDatabase()->createQueryBuilder('SHOW');
	}

	/**
	 * Creates a query builder with the UPDATE command.
	 *
	 * @return UpdateHelper
	 */
	public function update()
	{
		return $this->getDatabase()->createQueryBuilder('UPDATE');
	}

	/**
	 * Gets the database instance from the kernel. Note that the kernel will create the instance if it hasn't been
	 * loaded already.
	 *
	 * @return Database
	 */
	public function getDatabase()
	{
		return Application::kernel()->database()->get($this->name);
	}

	/**
	 * Starts a transaction.
	 *
	 * @return bool
	 */
	public function transaction()
	{
		try {
			$this->query('START TRANSACTION;');
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
	public function commit()
	{
		try {
			$this->query('COMMIT;');
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
	public function rollback()
	{
		try {
			$this->query('ROLLBACK;');
			return true;
		}
		catch (DatabaseException $e) {
			return false;
		}
	}

}
