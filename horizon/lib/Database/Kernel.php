<?php

namespace Horizon\Database;

use Horizon\Database\Exception\DatabaseException;
use Horizon\Exception\HorizonException;
use Horizon\Foundation\Application;

/**
 * Kernel for database connectivity and interaction.
 */
class Kernel {

	/**
	 * Loaded databases.
	 * @var Database[]
	 */
	private $databases = array();

	/**
	 * @var bool
	 */
	private $sandboxEnabled = false;

	/**
	 * @var bool
	 */
	private $validationEnabled = false;

	/**
	 * Gets (and loads if necessary) the database with the specified name. If no name is specified, then the default
	 * database is used (usually 'main').
	 *
	 * @param string|null $name
	 * @return Database
	 *
	 * @throws DatabaseException
	 * @throws HorizonException
	 */
	public function get($name = null) {
		if (is_null($name)) {
			$name = static::getDefaultName();

			if (is_null($name)) {
				throw new DatabaseException('No database is configured');
			}
		}

		if (!isset($this->databases[$name])) {
			if (!$this->load($name)) {
				throw new DatabaseException('No database configuration found with key "' . $name . '"');
			}
		}

		return $this->databases[$name];
	}

	/**
	 * Closes the database instance with the given name, or all instances if no name is provided.
	 *
	 * @param string|null $name
	 * @return bool
	 */
	public function close($name = null) {
		if (is_null($name)) {
			foreach ($this->databases as $database) {
				$database->close();
			}

			return true;
		}

		if (isset($this->databases[$name])) {
			$this->databases[$name]->close();
			return true;
		}

		return false;
	}

	/**
	 * Gets (and loads if necessary) the database with the specified name. If no name is specified, then the default
	 * database is used (usually 'main').
	 *
	 * @param string|null $name
	 * @return bool
	 *
	 * @throws DatabaseException
	 * @throws HorizonException
	 */
	private function load($name = null) {
		if (is_null($name)) {
			$name = $this->getDefaultName();

			if (is_null($name)) {
				throw new DatabaseException('No database is configured');
			}
		}

		$config = $this->getConfiguration($name);

		if (is_null($config)) {
			return false;
		}

		$this->databases[$name] = new Database($config);
		return true;
	}

	/**
	 * Gets the name of the default database. Returns null if no databases are configured.
	 *
	 * @return string|null
	 */
	private static function getDefaultName() {
		$databases = config('database');

		if (isset($databases['main'])) {
			return 'main';
		}

		foreach ($databases as $name => $db) {
			return $name;
		}

		return null;
	}

	/**
	 * Gets the configuration for the specified database name. If no database name is provided, it will return the
	 * default database's configuration. If no matches are found, or no databases are available, it will return null.
	 *
	 * @param string|null $name
	 * @return array|null
	 * @throws HorizonException
	 */
	private static function getConfiguration($name = null) {
		$databases = Application::config('database');

		// For a null name, find the default database config
		if (is_null($name)) {
			if (isset($databases['main'])) return $databases['main'];
			foreach ($databases as $db) return $db;
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
	 * Sets whether database connections should run in a sandboxed mode. When true, all queries sent to databases will
	 * be accepted and logged as normal, but the queries will not actually execute.
	 *
	 * If a boolean is not supplied, returns the current state.
	 *
	 * @param bool|null $enabled
	 * @return bool
	 */
	public function sandboxMode($enabled = null) {
		if (is_null($enabled)) {
			return $this->sandboxEnabled;
		}

		if ($enabled && $this->validationEnabled) {
			$this->validationEnabled = false;
		}

		return $this->sandboxEnabled = $enabled;
	}

	/**
	 * Sets whether database connections should run in a validation mode. When true, all queries sent to databases will
	 * be accepted and logged as normal, but the queries will not fully execute. Instead, they will be compiled using
	 * prepared statements (when supported) to check for syntax errors.
	 *
	 * If a boolean is not supplied, returns the current state.
	 *
	 * @param bool|null $enabled
	 * @return bool
	 */
	public function validationMode($enabled = null) {
		if (is_null($enabled)) {
			return $this->validationEnabled;
		}

		if ($enabled && $this->sandboxEnabled) {
			$this->sandboxEnabled = false;
		}

		return $this->validationEnabled = $enabled;
	}

	/**
	 * Returns an array of all registered databases.
	 *
	 * @return Database[]
	 */
	public function getDatabases() {
		$databases = array();

		foreach (config('database') as $name => $config) {
			$databases[$name] = $this->get($name);
		}

		return $databases;
	}

	/**
	 * Returns an array of connection interfaces for all registered databases.
	 *
	 * @return DatabaseConnection[]
	 */
	public function getConnections() {
		$connections = array();

		foreach (config('database') as $name => $config) {
			$connections[$name] = DatabaseFacade::connection($name);
		}

		return $connections;
	}

}
