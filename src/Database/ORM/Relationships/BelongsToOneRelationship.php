<?php

namespace Horizon\Database\ORM\Relationships;

use Horizon\Database\ORM\Relationship;
use Horizon\Database\Model;
use Horizon\Database\Exception\DatabaseException;

class BelongsToOneRelationship extends Relationship {

	protected $model;
	protected $foreignModelName;
	protected $foreignKey;
	protected $localKey;
	protected $foreignTableName;

	public function __construct(Model $model, $foreignModelName, $foreignKey, $localKey) {
		$foreignModel = new $foreignModelName;

		$this->model = $model;
		$this->foreignModelName = $foreignModelName;
		$this->foreignKey = $foreignKey;
		$this->localKey = $localKey;
		$this->foreignTableName = $foreignModel->getTable();

		unset($foreignModel);

		$this->query = \DB::select()->from($this->foreignTableName);
		$this->query->where($this->foreignKey, '=', $this->model->{$this->localKey});
		$this->query->limit(1);
		$this->query->setModel($this->foreignModelName);
	}

	public function get() {
		if (is_null($this->model->{$this->localKey})) {
			return null;
		}

		$connection = \DB::connection($this->model->getConnection());
		$cache = $connection->cache()->getModelInstance($this->foreignModelName, $this->model->{$this->localKey});

		if (!is_null($cache)) {
			return $cache;
		}

		$results = $this->query->get();

		if (isset($results[0])) {
			$instance = $results[0];
			$connection->cache()->saveModelInstance($instance);
			return $instance;
		}
	}

	public function set(Model $model) {
		$localKey = $this->localKey;
		$foreignKey = $this->foreignKey;

		$this->model->$localKey = $model->$foreignKey;
		$this->model->save();
		$this->clearCache();
	}

	public function attach($model) {
		$id = (is_object($model)) ? $model->getPrimaryKeyValue() : $model;

		if (is_null($id) || is_object($id)) {
			throw new DatabaseException('ORM: Cannot bind because the model type is not supported.');
		}

		$query = \DB::update()->table($this->model->getTable());
		$query->values(array(($this->localKey) => $id));
		$query->where($this->model->getPrimaryKey(), '=', $this->model->getPrimaryKeyValue());
		$query->exec();
		$this->clearCache();
	}

	public function detach() {
		$query = \DB::update()->table($this->model->getTable());
		$query->values(array(($this->localKey) => null));
		$query->where($this->model->getPrimaryKey(), '=', $this->model->getPrimaryKeyValue());
		$query->exec();
		$this->clearCache();
	}

	public function clearCache() {
		$connection = \DB::connection($this->model->getConnection());
		$connection->cache()->clearModelInstance($this->foreignModelName, $this->model->{$this->localKey});
	}

}
