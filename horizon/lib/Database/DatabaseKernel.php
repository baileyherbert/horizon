<?php

namespace Horizon\Database;

use Horizon\Database\Migration\Schema;

trait DatabaseKernel
{

    /**
     * @var Database The current database instance.
     */
    protected static $database;

    /**
     * Loads the database instance.
     */
    protected static function loadDatabase()
    {
        $config = config('database');
        static::$database = new Database($config);
    }

    /**
     * Gets the database instance.
     *
     * @return Database
     */
    public static function getDatabase()
    {
        if (is_null(static::$database)) {
            static::loadDatabase();
        }

        return static::$database;
    }

    /**
     * Gets the database instance.
     *
     * @return Database
     */
    protected static function closeDatabase()
    {
        if (!is_null(static::$database)) {
            static::$database->close();
        }
    }

}
