<?php

namespace Horizon\Database\ORM\Relationships;

use Horizon\Database\ORM\Relationship;
use Horizon\Database\Model;

class OneToManyRelationship extends Relationship {

	protected $model;
	protected $foreignModelName;
	protected $foreignKey;
	protected $localKey;
	protected $foreignTableName;
	protected $cache;
	protected $cacheCount;

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
		$this->query->setModel($this->foreignModelName);
	}

	public function get() {
		if ($this->cache) {
			return $this->cache;
		}

		return $this->cache = $this->query->get();
	}

	public function first() {
		$result = $this->query->first();
		return $result;
	}

	public function count() {
		if ($this->cacheCount) {
			return $this->cacheCount;
		}

		return $this->cacheCount = $this->query->count();
	}

	public function attach(Model $model) {
		$foreignKey = $this->foreignKey;

		$model->$foreignKey = $this->model->{$this->localKey};
		$model->save();
		$this->clearCache();
	}

	public function detach($model) {
		$query = \DB::update()->table($this->foreignTableName);
		$query->where($this->foreignKey, '=', $this->model->{$this->localKey});
		$query->andWhere($model->getPrimaryKey(), '=', $model->getPrimaryKeyValue());
		$query->values(array(
			($this->foreignKey) => null
		));
		$query->limit(1);
		$query->exec();
		$this->clearCache();
	}

	public function clearCache() {
		$this->cache = null;
		$this->cacheCount = 0;
	}

}
