<?php

namespace Horizon\Database;

use Horizon\Events\EventEmitter;
use Horizon\Database\Drivers\DriverInterface;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Utils\TimeProfiler;

class Database extends EventEmitter
{

    /**
     * @var array
     */
    protected $config;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var array
     */
    protected $log = array();

    /**
     * @var bool
     */
    protected $loggingEnabled = false;

    /**
     * Constructs a new Database instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loggingEnabled = $config['query_logging'] == true;

        $this->doQueryLog();
        $this->loadDriver();
    }

    /**
     * Loads the driver.
     *
     * @throws DatabaseException when no drivers are supported.
     */
    protected function loadDriver()
    {
        $this->driver = $this->getBestDriver();

        if (is_null($this->driver)) {
            throw new DatabaseException('Failed to start database because no supported extensions were found');
        }
    }

    /**
     * Gets the best available driver on the system, factoring in the preferred driver;
     *
     * @return DriverInterface|null
     */
    protected function getBestDriver()
    {
        $drivers = $this->getDrivers();
        $preferredDriver = config('database.preferred_driver', 'none');

        if (isset($drivers[$preferredDriver])) {
            $class = $drivers[$preferredDriver];
            return new $class($this);
        }

        foreach ($drivers as $class) {
            return new $class($this);
        }

        return null;
    }

    /**
     * Gets an array of information about the available drivers and chosen driver.
     *
     * @return array
     */
    public function getDriverDetails()
    {
        $drivers = $this->getDrivers();
        $preferredDriver = config('database.preferred_driver', 'none');
        $selected = null;

        if (isset($drivers[$preferredDriver])) {
            $selected = $drivers[$preferredDriver];
        }

        if (is_null($selected)) {
            foreach ($drivers as $class) {
                $selected = $class;
                break;
            }
        }

        return array(
            'installed' => $drivers,
            'preferred' => $preferredDriver,
            'chosen' => $selected
        );
    }

    /**
     * Gets an array of available drivers on the system.
     *
     * @return array
     */
    protected function getDrivers()
    {
        static $drivers = array(
            'mysqli' => 'Horizon\Database\Drivers\ImprovedDriver',
            'pdo' => 'Horizon\Database\Drivers\PdoDriver',
            'mysql' => 'Horizon\Database\Drivers\LegacyDriver'
        );

        static $installed = array();

        if (empty($installed)) {
            foreach ($drivers as $driver => $class) {
                if (call_user_func("{$class}::supported")) {
                    $installed[$driver] = $class;
                }
            }
        }

        return $installed;
    }

    /**
     * Executes a query.
     *
     * @param string $statement
     * @param array $bindings
     * @return array|int|bool
     */
    public function query($statement, array $bindings = array())
    {
        // Start timing the query
        TimeProfiler::start('horizon:database:query');

        // Run the query on the driver
        $returned = $this->driver->query($statement, $bindings);

        // Stop timing and get the number of milliseconds taken
        $timeTaken = TimeProfiler::stop('horizon:database:query');

        // Emit
        $this->emit('query', $statement, $bindings, $timeTaken);

        return $returned;
    }

    /**
     * Creates a new query builder for this database connection.
     *
     * @param string|null $type
     * @return QueryBuilder
     */
    public function createQueryBuilder($type = null)
    {
        $type = strtolower($type);
        $builder = new QueryBuilder($this->getPrefix(), $this);

        if (is_null($type)) {
            return $builder;
        }

        return $builder->$type();
    }

    /**
     * Gets the configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Gets the host of the database connection.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->config['host'];
    }

    /**
     * Gets the prefix for database tables.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->config['prefix'];
    }

    /**
     * Gets the name of the database.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->config['database'];
    }

    /**
     * Gets the username of the connection.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->config['user'];
    }

    /**
     * Gets the default character set.
     *
     * @return string
     */
    public function getCharacterSet()
    {
        return $this->config['charset'];
    }

    /**
     * Gets the default character set collation.
     *
     * @return string
     */
    public function getCollation()
    {
        return $this->config['collation'];
    }

    /**
     * Gets the current driver instance.
     *
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Gets an array of queries. Each query is an associative array containing statement, bindings, prepared, and
     * duration.
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->log;
    }

    /**
     * Sets whether query logging is enabled, overriding the configured default.
     *
     * @param bool $enabled
     */
    public function setQueryLogging($enabled = true)
    {
        $this->loggingEnabled = $enabled;
    }

    /**
     * Internal function for recording query logs.
     */
    protected function doQueryLog()
    {
        $this->on('query', function ($statement, array $bindings, $millisTaken) {
            if (!$this->loggingEnabled) {
                return;
            }

            $this->log[] = array(
                'query' => $statement,
                'prepared' => !empty($bindings),
                'bindings' => count($bindings),
                'reused' => false,
                'duration' => $millisTaken
            );
        });
    }

    /**
     * Closes the database.
     */
    public function close()
    {
        $this->driver->close();
    }

}
