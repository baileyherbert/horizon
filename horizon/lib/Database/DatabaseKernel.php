<?php

namespace Horizon\Database;

use Horizon\Database\Migration\Schema;
use Horizon\Database\Exception\DatabaseException;

trait DatabaseKernel
{

    /**
     * @var Database[] The current database instances.
     */
    protected static $databases = array();

    /**
     * Loads the database instance.
     *
     * @param string $name
     * @return bool
     */
    protected static function loadDatabase($name = null)
    {
        if (is_null($name)) {
            $name = static::getDefaultDatabaseName();

            if (is_null($name)) {
                throw new DatabaseException('No database is configured');
            }
        }

        $config = static::getDatabaseConfig($name);

        if (is_null($config)) {
            return false;
        }

        static::$databases[$name] = new Database($config);
        return true;
    }

    /**
     * Gets the database instance.
     *
     * @return Database
     */
    public static function getDatabase($name = null)
    {
        if (is_null($name)) {
            $name = static::getDefaultDatabaseName();

            if (is_null($name)) {
                throw new DatabaseException('No database is configured');
            }
        }

        if (!isset(static::$databases[$name])) {
            if (!static::loadDatabase($name)) {
                throw new DatabaseException('No database configuration found with key "' . $name . '"');
            }
        }

        return static::$databases[$name];
    }

    /**
     * Closes the database instance.
     *
     * @return bool
     */
    protected static function closeDatabase($name = null)
    {
        if (is_null($name)) {
            foreach (static::$databases as $database) {
                $database->close();
            }

            return true;
        }

        if (isset(static::$databases[$name])) {
            static::$databases[$name]->close();
            return true;
        }

        return false;
    }

    /**
     * Gets the configuration for the specified database name. If no database name is provided, it will return the
     * default database's configuration. If no matches are found, or no databases are available, it will return null.
     *
     * @param string|null $name
     * @return array|null
     */
    protected static function getDatabaseConfig($name = null)
    {
        $databases = config('database');

        // For a null name, find the default database config
        if (is_null($name)) {
            if (isset($databases['main'])) return $databases['main'];
            foreach ($databases as $db) return $dv;
            return null;
        }

        // Return the requested database
        if (isset($databases[$name])) {
            return $databases[$name];
        }

        // No match or no databases configured
        return null;
    }

    /**
     * Gets the name of the default database. Returns null if no databases are configured.
     *
     * @return string|null
     */
    protected static function getDefaultDatabaseName()
    {
        $databases = config('database');

        if (isset($databases['main'])) {
            return 'main';
        }

        foreach ($databases as $name => $db) {
            return $name;
        }

        return null;
    }

}
