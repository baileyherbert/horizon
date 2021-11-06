<?php

namespace Horizon\Database;

class Cache
{

	/**
	 * @var Model[][]
	 */
	private static $models = array();

	/**
	 * Checks if there is a cached instance for a model given its classname and primary key.
	 *
	 * @param Model|string $model The fully-qualified class name or an instance of the model.
	 * @param int $id The primary key value for the model row.
	 *
	 * @return bool
	 */
	public static function hasModelInstance($model, $id)
	{
		if (!config('database.cache', true)) {
			return false;
		}

		if (is_object($model)) {
			$model = get_class($model);
		}

		if (!isset(static::$models[$model])) {
			return false;
		}

		if (!isset(static::$models[$model][$id])) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the cached instance for a model given its classname and primary key.
	 *
	 * @param Model|string $model The fully-qualified class name or an instance of the model.
	 * @param int $id The primary key value for the model row.
	 * @param mixed $default What to return when the cache does not exist.
	 *
	 * @return Model|mixed
	 */
	public static function getModelInstance($model, $id, $default = null)
	{
		if (!config('database.cache', true)) {
			return $default;
		}

		if (is_object($model)) {
			$model = get_class($model);
		}

		if (static::hasModelInstance($model, $id)) {
			return static::$models[$model][$id];
		}

		return $default;
	}

	/**
	 * Removes a cached instance by its classname and primary key, if it exists.
	 *
	 * @param Model|string $model The fully-qualified class name or an instance of the model.
	 * @param int $id The primary key value for the model row.
	 *
	 * @return bool
	 */
	public static function removeModelInstance($model, $id)
	{
		if (!config('database.cache', true)) {
			return false;
		}

		if (is_object($model)) {
			$model = get_class($model);
		}

		if (isset(static::$models[$model])) {
			foreach (static::$models[$model] as $key => $instance) {
				if ($key == $id) {
					unset(static::$models[$model][$key]);
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Sets a cached instance by its classname and primary key.
	 *
	 * @param Model|string $model The fully-qualified class name or an instance of the model.
	 * @param Model $instance The row instance.
	 * @param int $id The primary key value to use, or null to determine from the model automatically.
	 *
	 * @return void
	 */
	public static function setModelInstance($model, Model $instance, $id = null)
	{
		if (!config('database.cache', true)) {
			return;
		}

		if (is_object($model)) {
			$model = get_class($model);
		}

		if (!isset(static::$models[$model])) {
			static::$models[$model] = array();
		}

		// Find the id from the instance
		if (is_null($id)) {
			$id = $instance->getPrimaryKeyValue();
		}

		// Save the instance
		static::$models[$model][$id] = $instance;

		// Listen for model deletion
		$instance->once('deleted', function() use ($model, $id) {
			static::removeModelInstance($model, $id);
		});

		// Listen for primary key changes
		$instance->on('property', function($propName, $propValue) use ($model, $id, $instance) {
			if ($propName == $instance->getPrimaryKey()) {
				static::removeModelInstance($model, $id);
				static::setModelInstance($model, $instance, $propValue);
			}
		});
	}

}
