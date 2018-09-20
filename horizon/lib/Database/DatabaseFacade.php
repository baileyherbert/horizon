<?php

namespace Horizon\Database;

use Horizon\Framework\Kernel;

use Horizon\Database\QueryBuilder\Documentation\AlterHelper;
use Horizon\Database\QueryBuilder\Documentation\CreateHelper;
use Horizon\Database\QueryBuilder\Documentation\DeleteHelper;
use Horizon\Database\QueryBuilder\Documentation\DropHelper;
use Horizon\Database\QueryBuilder\Documentation\InsertHelper;
use Horizon\Database\QueryBuilder\Documentation\SelectHelper;
use Horizon\Database\QueryBuilder\Documentation\ShowHelper;
use Horizon\Database\QueryBuilder\Documentation\UpdateHelper;

class DatabaseFacade
{

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
        return static::getDatabase()->query($statement, $bindings);
    }

    /**
     * Creates a query builder with the ALTER command.
     *
     * @return AlterHelper
     */
    public static function alter()
    {
        return static::getDatabase()->createQueryBuilder('ALTER');
    }

    /**
     * Creates a query builder with the CREATE command.
     *
     * @return CreateHelper
     */
    public static function create()
    {
        return static::getDatabase()->createQueryBuilder('CREATE');
    }

    /**
     * Creates a query builder with the DELETE command.
     *
     * @return DeleteHelper
     */
    public static function delete()
    {
        return static::getDatabase()->createQueryBuilder('DELETE');
    }

    /**
     * Creates a query builder with the DROP command.
     *
     * @return DropHelper
     */
    public static function drop()
    {
        return static::getDatabase()->createQueryBuilder('DROP');
    }

    /**
     * Creates a query builder with the INSERT command.
     *
     * @return InsertHelper
     */
    public static function insert()
    {
        return static::getDatabase()->createQueryBuilder('INSERT');
    }

    /**
     * Creates a query builder with the SELECT command.
     *
     * @return SelectHelper
     */
    public static function select()
    {
        return static::getDatabase()->createQueryBuilder('SELECT');
    }

    /**
     * Creates a query builder with the SHOW command.
     *
     * @return ShowHelper
     */
    public static function show()
    {
        return static::getDatabase()->createQueryBuilder('SHOW');
    }

    /**
     * Creates a query builder with the UPDATE command.
     *
     * @return UpdateHelper
     */
    public static function update()
    {
        return static::getDatabase()->createQueryBuilder('UPDATE');
    }

    /**
     * Gets the database instance from the kernel. Note that the kernel will create the instance if it hasn't been
     * loaded already.
     *
     * @return Database
     */
    protected static function getDatabase()
    {
        return Kernel::getDatabase();
    }

}