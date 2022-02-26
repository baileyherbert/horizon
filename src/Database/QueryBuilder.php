<?php

namespace Horizon\Database;

use Horizon\Database\QueryBuilder\Commands\CommandInterface;
use Horizon\Database\Exception\QueryBuilderException;
use Horizon\Database\QueryBuilder\Commands\Select;

/**
 * @method \Horizon\Database\QueryBuilder\Documentation\AlterHelper alter()
 * @method \Horizon\Database\QueryBuilder\Documentation\CreateHelper create()
 * @method \Horizon\Database\QueryBuilder\Documentation\DeleteHelper delete()
 * @method \Horizon\Database\QueryBuilder\Documentation\DropHelper drop()
 * @method \Horizon\Database\QueryBuilder\Documentation\InsertHelper insert()
 * @method \Horizon\Database\QueryBuilder\Documentation\SelectHelper select()
 * @method \Horizon\Database\QueryBuilder\Documentation\ShowHelper show()
 * @method \Horizon\Database\QueryBuilder\Documentation\UpdateHelper update()
 */
class QueryBuilder {

	/**
	 * @var string Prefix for table names.
	 */
	protected $prefix;

	/**
	 * @var CommandInterface
	 */
	protected $command;

	/**
	 * @var Database
	 */
	protected $database;

	/**
	 * @var string
	 */
	protected $model;

	/**
	 * Constructs a new QueryBuilder instance.
	 *
	 * @param string $prefix
	 * @param Database|null $database
	 */
	public function __construct($prefix = null, $database = null) {
		$this->prefix = $prefix;
		$this->database = $database;
	}

	/**
	 * Sets the prefix to use for table names.
	 *
	 * @param string $prefix
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}

	/**
	 * Gets the prefix to use for table names.
	 *
	 * @param string $prefix
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Magic method for loading commands or calling methods on command instances.
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		if (is_null($this->command)) {
			return $this->startCommand($method, $arguments);
		}
		else {
			return $this->runCommandMethod($method, $arguments);
		}
	}

	/**
	 * Sets the model to create for results.
	 *
	 * @param string $model
	 * @return $this
	 */
	public function setModel($model) {
		$this->model = $model;
		return $this;
	}

	/**
	 * Initializes the command instance for the query builder.
	 *
	 * @param string $method The name of the command.
	 * @param array $arguments Arguments provided to the call (can be empty).
	 * @return $this
	 */
	protected function startCommand($method, $arguments) {
		// Commands have no parameters
		if (count($arguments) !== 0) {
			throw new QueryBuilderException(sprintf('%s() expected exactly 0 arguments, got %d', $method, count($arguments)));
		}

		// Make sure the command exists
		if (!$this->hasCommand($method)) {
			throw new QueryBuilderException(sprintf('%s is not a supported query command', $method));
		}

		// Create the new command instance
		$this->command = $this->getCommand($method);

		// Return this instance
		return $this;
	}

	/**
	 * Executes a method on the command instance.
	 *
	 * @param string $method The name of the method.
	 * @param array $arguments Arguments provided to the call.
	 * @return $this|mixed
	 */
	protected function runCommandMethod($method, $arguments) {
		if (!method_exists($this->command, $method)) {
			throw new QueryBuilderException(sprintf('Command has no such method \'%s\'', $method));
		}

		$returned = call_user_func_array(array($this->command, $method), $arguments);

		if ($returned == $this->command) {
			return $this;
		}

		return $returned;
	}

	/**
	 * Gets an array of commands the query builder supports.
	 *
	 * @return string[]
	 */
	protected function getCommands() {
		static $commands = array(
			'Alter',
			'Create',
			'Delete',
			'Describe',
			'Drop',
			'Insert',
			'Select',
			'Show',
			'Update'
		);

		return $commands;
	}

	/**
	 * Checks whether the builder supports the specified command.
	 *
	 * @param string $command
	 * @return bool
	 */
	protected function hasCommand($command) {
		$commands = $this->getCommands();

		foreach ($commands as $c) {
			if (strcasecmp($command, $c) === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets an instance for the specified command.
	 *
	 * @param string $command
	 * @return CommandInterface|null
	 */
	protected function getCommand($command) {
		$commands = $this->getCommands();

		foreach ($commands as $c) {
			if (strcasecmp($command, $c) === 0) {
				$className = 'Horizon\Database\QueryBuilder\Commands\\' . $c;

				if (class_exists($className)) {
					return new $className($this);
				}
			}
		}

		return null;
	}

	/**
	 * Gets an array of matching results as objects.
	 *
	 * @return Model[]|object[]
	 */
	public function get() {
		$results = $this->database->query($this->compile(), $this->getParameters());

		return $this->mapToModels($results);
	}

	/**
	 * Executes the given callback for each row.
	 *
	 * @param callable $callback
	 * @return void
	 */
	public function each($callback) {
		return $this->database->query($this->compile(), $this->getParameters(), $callback);
	}

	/**
	 * Runs the query and returns the number of rows.
	 *
	 * @return int
	 */
	public function count() {
		if (!($this->command instanceof Select)) {
			return null;
		}

		$this->columns('COUNT(*)');
		$this->setModel(null);

		$results = $this->database->query($this->compile(), $this->getParameters());
		$row = array_shift($results);

		return intval($row->{'COUNT(*)'});
	}

	/**
	 * Runs the query and returns the raw result from the database.
	 *
	 * @return bool|object|int
	 */
	public function exec() {
		$result = $this->database->query($this->compile(), $this->getParameters());

		return $result;
	}

	/**
	 * Gets an object for the first matching result, or null if not found.
	 *
	 * @return Model|object|null
	 */
	public function first() {
		// Set the limit to 1 row
		$this->limit(1);

		// Get the results
		$results = $this->get();

		// Return the first result
		if (!empty($results)) {
			return $results[0];
		}

		return null;
	}

	/**
	 * Converts an array of row objects into an array of model instances.
	 *
	 * @return Model[]|object[]
	 */
	protected function mapToModels(&$results) {
		$models = array();
		$className = $this->model;

		if (is_null($className)) {
			return $results;
		}

		// Find the primary key name
		$o = new $className;
		$primaryKeyName = $o->getPrimaryKey();

		foreach ($results as $result) {
			if (is_string($primaryKeyName)) {
				if (property_exists($result, $primaryKeyName)) {
					$keyValue = $result->{$primaryKeyName};

					if ($cached = $this->database->cache()->getModelInstance($className, $keyValue)) {
						$models[] = $cached;
						continue;
					}
				}
			}
			else if (is_array($primaryKeyName)) {
				$keyValues = array_map(function($keyName) use ($result) {
					if (property_exists($result, $keyName)) {
						return $result->{$keyName};
					}
				}, $primaryKeyName);

				if (!in_array(null, $keyValues)) {
					if ($cached = $this->database->cache()->getModelInstance($className, $keyValues)) {
						$models[] = $cached;
						continue;
					}
				}
			}

			$models[] = new $className($result);
		}

		return $models;
	}

	/**
	 * Compiles the query as a prepared statement and returns the string.
	 *
	 * @return string
	 */
	public function __toString() {
		if (is_null($this->command)) {
			throw new QueryBuilderException('Cannot convert to string: query builder has no command');
		}

		return $this->command->compile();
	}

}
