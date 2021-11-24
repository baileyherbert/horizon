<?php

namespace Horizon\Database;

/**
 * This class manages the internal cache for a database.
 */
class DatabaseCache {

	/**
	 * @var Database
	 */
	private $database;

	private $models = [];
	private $relationships = [];

	public function __construct(Database $database) {
		$this->database = $database;
	}

	/**
	 * Records the given model instance.
	 *
	 * @param Model $model
	 * @param mixed $primaryKeyValue
	 * @return void
	 */
	public function saveModelInstance(Model $model) {
		if (!$this->isCachingEnabled()) {
			return;
		}

		$primaryKeyValue = $model->getPrimaryKeyValue();
		$index = serialize($primaryKeyValue);
		$className = get_class($model);

		if (!array_key_exists($className, $this->models)) {
			$this->models[$className] = [];
		}

		$this->models[$className][$index] = $model;
	}

	/**
	 * Returns a model instance matching the given primary key value.
	 *
	 * @param string $className The model's full class name.
	 * @param mixed $primaryKeyValue
	 * @return Model|null
	 */
	public function getModelInstance($className, $primaryKeyValue) {
		if (!$this->isCachingEnabled()) {
			return;
		}

		if (array_key_exists($className, $this->models)) {
			if (array_key_exists($index = serialize($primaryKeyValue), $this->models[$className])) {
				return $this->models[$className][$index];
			}
		}
	}

	/**
	 * Checks if the cache has a matching model instance.
	 *
	 * @param string $className The model's full class name.
	 * @param mixed $primaryKeyValue
	 * @return bool
	 */
	public function hasModelInstance($className, $primaryKeyValue) {
		return
			$this->isCachingEnabled() &&
			array_key_exists($className, $this->models) &&
			array_key_exists(serialize($primaryKeyValue), $this->models[$className]);
	}

	/**
	 * Removes the specified model instance if it exists in the cache.
	 *
	 * @param string $className The model's full class name.
	 * @param mixed $primaryKeyValue
	 * @return void
	 */
	public function clearModelInstance($className, $primaryKeyValue) {
		$index = serialize($primaryKeyValue);

		if (array_key_exists($className, $this->models)) {
			if (array_key_exists($index, $this->models[$className])) {
				unset($this->models[$className][$index]);
			}
		}
	}

	/**
	 * Saves a relationship's results.
	 *
	 * @param Model $model
	 * @param string $relationshipName
	 * @param mixed $result
	 * @return void
	 */
	public function saveRelationship(Model $model, $relationshipName, $result) {
		if (!$this->isCachingEnabled()) {
			return;
		}

		$index = spl_object_hash($model) . ':relationship:' . $relationshipName;
		$this->relationships[$index] = $result;
	}

	/**
	 * Returns a relationship's results if it's in the cache.
	 *
	 * @param Model $model
	 * @param string $relationshipName
	 * @return mixed|null
	 */
	public function getRelationship(Model $model, $relationshipName) {
		if (!$this->isCachingEnabled()) {
			return;
		}

		$index = spl_object_hash($model) . ':relationship:' . $relationshipName;

		if (array_key_exists($index, $this->relationships)) {
			return $this->relationships[$index];
		}
	}

	/**
	 * Checks if the cache has a relationship's results.
	 *
	 * @param Model $model
	 * @param string $relationshipName
	 * @return bool
	 */
	public function hasRelationship(Model $model, $relationshipName) {
		return
			$this->isCachingEnabled() &&
			array_key_exists(spl_object_hash($model) . ':relationship:' . $relationshipName, $this->relationships);
	}

	/**
	 * Clears a relationship's results from the cache.
	 *
	 * @param Model $model
	 * @param string $relationshipName
	 * @return void
	 */
	public function clearRelationship(Model $model, $relationshipName) {
		$index = spl_object_hash($model) . ':relationship:' . $relationshipName;

		if (array_key_exists($index, $this->relationships)) {
			unset($this->relationships[$index]);
		}
	}

	/**
	 * Returns `true` if caching is currently enabled for this database.
	 *
	 * @return bool
	 */
	public function isCachingEnabled() {
		return array_get($this->database->getConfig(), 'cache', true);
	}

}
