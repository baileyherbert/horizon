<?php

namespace Horizon\Database\ORM\Relationships;

use Horizon\Database\ORM\Relationship;
use Horizon\Database\Model;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Database\QueryBuilder\StringBuilder;

class BelongsToOneRelationship extends Relationship
{

    protected $model;
    protected $foreignModelName;
    protected $foreignKey;
    protected $localKey;
    protected $foreignTableName;

    public function __construct(Model $model, $foreignModelName, $foreignKey, $localKey)
    {
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

    public function get()
    {
        if (is_null($this->model->{$this->localKey})) {
            return null;
        }

        $results = $this->query->get();

        if (isset($results[0])) {
            return $results[0];
        }
    }

    public function set(Model $model)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        $this->model->$localKey = $model->$foreignKey;
        $this->model->save();
    }

    public function attach($model)
    {
        $id = (is_object($model)) ? $model->getPrimaryKeyValue() : $model;

        if (!is_numeric($id) && !is_null($id)) {
            throw new DatabaseException('ORM: Cannot bind because the model type is not supported.');
        }

        $query = \DB::update()->table($this->model->getTable());
        $query->values(array(($this->localKey) => $id));
        $query->where($this->model->getPrimaryKey(), '=', $this->model->getPrimaryKeyValue());
        $query->exec();
    }

    public function detach()
    {
        $query = \DB::update()->table($this->model->getTable());
        $query->values(array(($this->localKey) => null));
        $query->where($this->model->getPrimaryKey(), '=', $this->model->getPrimaryKeyValue());
        $query->exec();
    }

}
