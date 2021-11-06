<?php

namespace Horizon\Database\ORM\Relationships;

use Horizon\Database\ORM\Relationship;
use Horizon\Database\Model;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Database\Exception\DatabaseException;

class BelongsToManyRelationship extends Relationship {

	protected $model;
	protected $foreignModelName;
	protected $foreignKey;
	protected $localKey;
	protected $mapTable;

	public function __construct(Model $model, $foreignModelName, $foreignKey, $localKey, $mapTable = null) {
		$foreignModel = new $foreignModelName;

		if (is_null($mapTable)) {
			$mapTable = StringBuilder::generateMappingTableName(get_class($model), $foreignModelName);
		}

		$this->model = $model;
		$this->foreignModelName = $foreignModelName;
		$this->foreignKey = $foreignKey;
		$this->localKey = $localKey;
		$this->foreignTableName = $foreignModel->getTable();
		$this->mapTable = $mapTable;

		$this->query = \DB::select()->from($foreignModel->getTable(), $mapTable);
		$this->query->columns($foreignModel->getTable() . '.*');
		$this->query->where($mapTable . '.' . $localKey, '=', $model->getPrimaryKeyValue());
		$this->query->where($foreignModel->getTable() . '.' . $foreignModel->getPrimaryKey(), '=', $mapTable . '.' . $foreignKey, 'AND', true);
		$this->query->setModel($foreignModelName);
	}

	public function get() {
		return $this->query->get();
	}

	public function attach($model) {
		$id = (is_object($model)) ? $model->getPrimaryKeyValue() : $model;

		if (!is_numeric($id) && !is_null($id)) {
			throw new DatabaseException('ORM: Cannot bind because the model type is not supported.');
		}

		$query = \DB::insert()->into($this->mapTable);
		$query->values(array($this->foreignKey => $id, $this->localKey => $this->model->getPrimaryKeyValue()));

		$query->exec();
	}

	public function detach($model) {
		$id = (is_object($model)) ? $model->getPrimaryKeyValue() : $model;

		if (!is_numeric($id) && !is_null($id)) {
			throw new DatabaseException('ORM: Cannot bind because the model type is not supported.');
		}

		$query = \DB::delete()->from($this->mapTable);
		$query->where($this->foreignKey, '=', $id);
		$query->where($this->localKey, '=', $this->model->getPrimaryKeyValue());

		$query->exec();
	}

}
