<?php

namespace Horizon\Database\Migration;

use Horizon\Database\DatabaseConnection;
use Horizon\Database\Exception\DatabaseDriverException;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Database\Exception\MigrationException;

/**
 * Utility class for migrating the database.
 */
class Migration
{

    /**
     * @var DatabaseConnection
     */
    private $connection;

    /**
     * Constructs a new Migration instance.
     *
     * @param DatabaseConnection $database
     */
    public function __construct(DatabaseConnection $database)
    {
        $this->connection = $database;
    }

    /**
     * Runs a migration via a callable.
     *
     * @param callable $callable
     * @return bool
     * @throws DatabaseException When a migration fails due to a query error.
     * @throws MigrationException When an illegal migration operation is requested.
     * @throws DatabaseDriverException When the database driver encounters a fatal error.
     */
    public function run($callable)
    {
        $schema = new Schema($this);
        call_user_func($callable, $schema, $this);

        return true;
    }

    /**
     * Gets the database connection for this migration.
     *
     * @return DatabaseConnection
     */
    public function connection()
    {
        return $this->connection;
    }

}
