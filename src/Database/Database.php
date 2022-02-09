<?php

namespace Horizon\Database;

use DateTime;
use DateTimeZone;
use Exception;
use Horizon\Events\EventEmitter;
use Horizon\Database\Drivers\DriverInterface;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Foundation\Application;
use Horizon\Support\Profiler;

class Database extends EventEmitter {

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
	 * @var Kernel
	 */
	protected $kernel;

	/**
	 * @var DatabaseCache
	 */
	protected $cache;

	/**
	 * Constructs a new Database instance.
	 *
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->kernel = Application::kernel()->database();
		$this->config = $config;
		$this->loggingEnabled = $config['query_logging'] == true;
		$this->cache = new DatabaseCache($this);

		$this->doQueryLog();
		$this->loadDriver();
	}

	/**
	 * Loads the driver.
	 *
	 * @throws DatabaseException when no drivers are supported.
	 */
	protected function loadDriver() {
		$event = Profiler::record('Initialize database driver');
		$this->driver = $this->getBestDriver();
		$event->extraInformation = get_class($this->driver);

		if (is_null($this->driver)) {
			throw new DatabaseException('Failed to start database because no supported extensions were found');
		}
	}

	/**
	 * Gets the best available driver on the system, factoring in the preferred driver;
	 *
	 * @return DriverInterface|null
	 */
	protected function getBestDriver() {
		$drivers = $this->getDrivers();
		$preferredDriver = isset($this->config['preferred_driver']) ? $this->config['preferred_driver'] : 'none';

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
	public function getDriverDetails() {
		$drivers = $this->getDrivers();
		$preferredDriver = isset($this->config['preferred_driver']) ? $this->config['preferred_driver'] : 'none';
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
	protected function getDrivers() {
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
	public function query($statement, array $bindings = array()) {
		try {
			$startTime = microtime(true);
			$returned = false;

			$isSandboxMode = $this->kernel->sandboxMode();
			$isValidationMode = $this->kernel->validationMode();

			if ($this->loggingEnabled) {
				Profiler::record('Database query: ' . $statement, $bindings);
			}

			// Run the query on the driver
			if (!$isSandboxMode && !$isValidationMode) {
				$returned = $this->driver->query($statement, $bindings);
			}
			else if ($isValidationMode) {
				$this->driver->validate($statement);
			}

			// Stop timing and get the number of milliseconds taken
			$timeTaken = microtime(true) - $startTime;

			// Emit
			$this->emit('query', $statement, $bindings, $timeTaken);
			Profiler::recordAsset('Database queries', null, $timeTaken);

			return $returned;
		}
		catch (Exception $ex) {
			$timeTaken = microtime(true) - $startTime;
			$this->emit('query', $statement, $bindings, $timeTaken, $ex);
			throw $ex;
		}
	}

	/**
	 * Creates a new query builder for this database connection.
	 *
	 * @param string|null $type
	 * @return QueryBuilder
	 */
	public function createQueryBuilder($type = null) {
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
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Gets the host of the database connection.
	 *
	 * @return string
	 */
	public function getHost() {
		return $this->config['host'];
	}

	/**
	 * Gets the prefix for database tables.
	 *
	 * @return string
	 */
	public function getPrefix() {
		return $this->config['prefix'];
	}

	/**
	 * Gets the name of the database.
	 *
	 * @return string
	 */
	public function getDatabaseName() {
		return $this->config['database'];
	}

	/**
	 * Gets the username of the connection.
	 *
	 * @return string
	 */
	public function getUserName() {
		return $this->config['user'];
	}

	/**
	 * Gets the default character set.
	 *
	 * @return string
	 */
	public function getCharacterSet() {
		return $this->config['charset'];
	}

	/**
	 * Gets the default character set collation.
	 *
	 * @return string
	 */
	public function getCollation() {
		return $this->config['collation'];
	}

	/**
	 * Gets the current driver instance.
	 *
	 * @return DriverInterface
	 */
	public function getDriver() {
		return $this->driver;
	}

	/**
	 * Gets an array of queries. Each query is an associative array containing statement, bindings, prepared, and
	 * duration.
	 *
	 * @return array
	 */
	public function getQueryLog() {
		return $this->log;
	}

	/**
	 * Sets whether query logging is enabled, overriding the configured default.
	 *
	 * @param bool $enabled
	 */
	public function setQueryLogging($enabled = true) {
		$this->loggingEnabled = $enabled;
	}

	/**
	 * Internal function for recording query logs.
	 */
	protected function doQueryLog() {
		$this->on('query', function ($statement, array $bindings, $millisTaken, $exception = null) {
			if (!$this->loggingEnabled) {
				return;
			}

			$this->log[] = array(
				'query' => $statement,
				'prepared' => !empty($bindings),
				'bindings' => $bindings,
				'duration' => $millisTaken,
				'exception' => $exception
			);

			if (count($this->log) > 512) {
				array_shift($this->log);
			}
		});
	}

	/**
	 * Closes the database.
	 */
	public function close() {
		$this->driver->close();
	}

	/**
	 * Sets the timezone of the connection. If a timezone is not specified, uses the application's global default
	 * timezone instead.
	 *
	 * @param string|null $timezone
	 * @return void
	 */
	public function setTimezone($timezone = null) {
		$timezone = new DateTimeZone($timezone ?: config('app.timezone', 'UTC'));
		$date = new DateTime('now', $timezone);
		$offset = $date->format('P');

		Profiler::record('Send database timezone', $offset);
		$this->driver->query("SET time_zone = '$offset';");
	}

	/**
	 * Returns the cache manager for this database.
	 *
	 * @return DatabaseCache
	 */
	public function cache() {
		return $this->cache;
	}

}
