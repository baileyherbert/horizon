<?php

namespace Horizon\Database\ORM\Relationships;

use Horizon\Database\ORM\Relationship;
use Horizon\Database\Model;

class OneToOneRelationship extends Relationship
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
        $results = $this->query->get();

        if (isset($results[0])) {
            return $results[0];
        }
    }

    public function attach(Model $model)
    {
        $foreignKey = $this->foreignKey;

        $model->$foreignKey = $this->{$this->model->localKey};
        $model->save();
    }

    public function detach()
    {
        $query = \DB::update()->table($this->foreignTableName);
        $query->values(array(
            ($this->foreignKey) => null
        ));
        $query->where($this->foreignKey, '=', $this->model->{$this->localKey});
        $query->limit(1);
        $query->exec();
    }

}