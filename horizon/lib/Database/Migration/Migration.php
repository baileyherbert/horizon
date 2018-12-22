<?php

namespace Horizon\Database\Migration;

use Horizon\Database\DatabaseConnection;
use Exception;

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
     */
    public function run($callable)
    {
        $schema = new Schema($this);

        try {
            call_user_func($callable, $schema, $this);
        }
        catch (Exception $e) {
            return false;
        }

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
