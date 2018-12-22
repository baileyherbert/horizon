<?php

namespace Horizon\Database\Migration;

/**
 * Utility class for building, managing, and testing database schemas.
 */
class Schema
{

    /**
     * @var Migration
     */
    private $migration;

    /**
     * Constructs a new Schema instance.
     *
     * @param Migration $migration
     */
    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * Spawns a table blueprint for creating a new table schema. The callable will be passed the Blueprint instance.
     *
     * @param string $name
     * @param callable $callable
     */
    public function create($name, $callable)
    {

    }

    /**
     * Spawns a table blueprint for modifying an existing table schema. The callable will be passed the Blueprint
     * instance.
     *
     * @param string $name
     * @param callable $callable
     */
    public function table($name, $callable)
    {

    }

    /**
     * Renames a table if it exists.
     *
     * @param string $from Current table name.
     * @param string $to New table name.
     */
    public function rename($from, $to)
    {

    }

    /**
     * Drops a table. Will error if the table does not exist.
     *
     * @param string $name
     */
    public function drop($name)
    {

    }

    /**
     * Checks if the table exists and drops it.
     *
     * @param string $name
     * @return bool
     */
    public function dropIfExists($name)
    {

    }

    /**
     * Updates the prefix on all tables in the database.
     *
     * @param string $from
     * @param string $to
     */
    public function prefix($from, $to)
    {

    }

    /**
     * Checks if the table exists in the database.
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {

    }

    /**
     * Checks if the column exists in the database.
     *
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    public function hasColumn($tableName, $columnName)
    {

    }

}
