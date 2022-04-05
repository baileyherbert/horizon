<?php

namespace Horizon\Database\ORM\Traits;

use DB;
use Horizon\Database\QueryBuilder\Documentation\DeleteHelper;
use Horizon\Database\QueryBuilder\Documentation\SelectHelper;
use Horizon\Http\Exception\HttpResponseException;

trait QueryBuilding {

	/**
	 * Gets all rows. Careful!
	 *
	 * @return static[]
	 */
	public static function all() {
		$o = (new static);

		$builder = DB::connection($o->getConnection())->select()->from($o->getTable());
		$builder->setModel(get_class($o));

		return $builder->get();
	}

	/**
	 * Gets a query builder object linked to this model.
	 *
	 * @param string $column
	 * @param string $operator
	 * @return SelectHelper
	 */
	public static function where($column, $operator, $equals) {
		$o = (new static);

		$builder = DB::connection($o->getConnection())->select()->from($o->getTable())->where($column, $operator, $equals);
		$builder->setModel(get_class($o));

		return $builder;
	}

	/**
	 * Creates a `SELECT` query builder for the model.
	 *
	 * @return SelectHelper
	 */
	public static function query() {
		$o = (new static);

		$builder = DB::connection($o->getConnection())->select()->from($o->getTable());
		$builder->setModel(get_class($o));

		return $builder;
	}

	/**
	 * Creates a `DELETE` query builder for the model.
	 *
	 * @return DeleteHelper
	 */
	public static function deleteFrom() {
		$o = (new static);

		$builder = DB::connection($o->getConnection())->delete()->from($o->getTable());

		return $builder;
	}

	/**
	 * Returns the total number of rows in the table for this model.
	 *
	 * @return int
	 */
	public static function count() {
		$o = (new static);
		return DB::connection($o->getConnection())->select()->from($o->getTable())->count();
	}

	/**
	 * Gets a model instance by the primary key value.
	 *
	 * @param mixed $primaryKey
	 * @return static
	 */
	public static function find($primaryKey) {
		$o = (new static);

		$connection = DB::connection($o->getConnection());
		$cache = $connection->cache()->getModelInstance(get_class($o), $primaryKey);

		if (!is_null($cache)) {
			return $cache;
		}

		$builder = $o->createSelectQuery($primaryKey)->limit(1);
		$builder->setModel(get_class($o));
		$row = $builder->first();

		// Cache the new model instance
		if (!is_null($row)) {
			$connection->cache()->saveModelInstance($row);
		}

		return $row;
	}

	/**
	 * Gets a model instance by the primary key value. If not found, it raises an HttpResponseException with the
	 * provided code (or default 404).
	 *
	 * @param mixed $primaryKey
	 * @param int $code
	 * @return static
	 */
	public static function findOrFail($primaryKey, $code = 404) {
		$model = static::find($primaryKey);

		if (is_null($model)) {
			throw new HttpResponseException($code);
		}

		return $model;
	}

	/**
	 * Creates a new row in the database and returns the model.
	 *
	 * @param array $values
	 * @param int $code
	 * @return static
	 */
	public static function create(array $values) {
		$o = (new static);

		$table = $o->getTable();

		$builder = DB::connection($o->getConnection())->insert()->into($table)->values($values);
		$id = $builder->exec();

		if (!$id) {
			return null;
		}

		return static::find($id);
	}

}
