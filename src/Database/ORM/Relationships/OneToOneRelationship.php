<?php

namespace Horizon\Database\ORM\Relationships;

use Horizon\Database\ORM\Relationship;
use Horizon\Database\Model;

class OneToOneRelationship extends Relationship {

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

		$model->$foreignKey = $this->model->$localKey;
		$model->save();
		$this->clearCache();
	}

	public function attach(Model $model) {
		$foreignKey = $this->foreignKey;

		$model->$foreignKey = $this->{$this->model->localKey};
		$model->save();
		$this->clearCache();
	}

	public function detach() {
		$query = \DB::update()->table($this->foreignTableName);
		$query->values(array(
			($this->foreignKey) => null
		));
		$query->where($this->foreignKey, '=', $this->model->{$this->localKey});
		$query->limit(1);
		$query->exec();
		$this->clearCache();
	}

	public function clearCache() {
		$connection = \DB::connection($this->model->getConnection());
		$connection->cache()->clearModelInstance($this->foreignModelName, $this->model->{$this->localKey});
	}

}
