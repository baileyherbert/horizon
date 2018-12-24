<?php

namespace Horizon\Database\Migration;

use Horizon\Database\Migration\Schema\Grammar;

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
     * @return bool
     */
    public function create($name, $callable)
    {
        $blueprint = new Blueprint($name, $this);
        $blueprint->create();

        $callable($blueprint);

        return $this->migration->connection()->query((string)$blueprint);
    }

    /**
     * Spawns a table blueprint for modifying an existing table schema. The callable will be passed the Blueprint
     * instance.
     *
     * @param string $name
     * @param callable $callable
     * @return bool
     */
    public function table($name, $callable)
    {
        $blueprint = new Blueprint($name, $this);
        $callable($blueprint);

        return $this->migration->connection()->query((string)$blueprint);
    }

    /**
     * Renames a table if it exists.
     *
     * @param string $from Current table name.
     * @param string $to New table name.
     * @return bool
     */
    public function rename($from, $to)
    {
        $query = str_join(
            'ALTER TABLE',
            Grammar::compileName($this->prefix($from)),
            'RENAME',
            Grammar::compileName($this->prefix($to))
        ) . ';';

        return $this->migration->connection()->query($query);
    }

    /**
     * Drops a table. Will error if the table does not exist.
     *
     * @param string $name
     * @return bool
     */
    public function drop($name)
    {
        $query = str_join(
            'DROP TABLE',
            Grammar::compileName($this->prefix($name))
        ) . ';';

        return $this->migration->connection()->query($query);
    }

    /**
     * Checks if the table exists and drops it.
     *
     * @param string $name
     * @return bool
     */
    public function dropIfExists($name)
    {
        $query = str_join(
            'DROP TABLE',
            'IF EXISTS',
            Grammar::compileName($this->prefix($name))
        ) . ';';

        return $this->migration->connection()->query($query);
    }

    /**
     * Gets the current prefix or prepends it to the given table name.
     *
     * @param string $name
     * @return string
     */
    public function prefix($name = null)
    {
        $prefix = $this->migration->connection()->getDatabase()->getPrefix();

        if (!is_null($name)) {
            return $prefix . $name;
        }

        return $prefix;
    }

    /**
     * Checks if the table exists in the database.
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {
        $query = str_join(
            'SHOW TABLES LIKE',
            Grammar::compileString($this->prefix($tableName))
        ) . ';';

        $rows = $this->migration->connection()->query($query);
        return count($rows) > 0;
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
        $query = str_join(
            'SHOW COLUMNS FROM',
            Grammar::compileName($this->prefix($tableName)),
            'LIKE',
            Grammar::compileString($columnName)
        ) . ';';

        $rows = $this->migration->connection()->query($query);
        return count($rows) > 0;
    }

}
