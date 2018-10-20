<?php

namespace Horizon\Database\ORM\Traits;

use Horizon\Database\Model;

use Horizon\Database\ORM\Relationship;
use Horizon\Database\QueryBuilder;
use Horizon\Framework\Kernel;
use Horizon\Database\Exception\DatabaseException;

trait Mapping
{

    use Relationships;

    protected $table;
    protected $primaryKey = 'id';
    protected $incrementing = true;
    protected $storage = array();
    protected $changes = array();

    /**
     * Gets the name of the table for this instance.
     *
     * @return string
     */
    public function getTable()
    {
        if (is_null($this->table)) {
            $tableNameParts = explode('\\', strtolower(get_class($this)));
            $tableName = array_pop($tableNameParts);

            if (substr($tableName, 0, -1) != 's') {
                $tableName .= 's';
            }

            $this->table = $tableName;
        }

        return $this->table;
    }

    /**
     * Gets the name of the primary key for this table.
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Gets the value of the primary key in the row.
     *
     * @return int|null
     */
    public function getPrimaryKeyValue()
    {
        $keyName = $this->getPrimaryKey();

        if (isset($this->storage[$keyName])) {
            return $this->storage[$keyName];
        }

        return null;
    }

    /**
     * Saves changes to the row or creates the row if it doesn't exist.
     */
    public function save()
    {
        $keyName = $this->getPrimaryKey();
        $keyValue = $this->getPrimaryKeyValue();

        if (empty($this->changes)) {
            return;
        }

        if (is_null($keyValue)) {
            $builder = \DB::insert()->into($this->getTable())->values($this->changes);
            $returned = $builder->exec();

            if (!isset($this->changes[$keyName])) {
                $this->changes[$keyName] = $returned;
            }

            $this->emit('inserted');
        }
        else {
            $builder = \DB::update()->table($this->getTable())->values($this->changes);
            $builder->where($keyName, '=', $keyValue);
            $builder->exec();

            $this->emit('updated');
        }

        foreach ($this->changes as $key => $value) {
            $this->storage[$key] = $value;
        }

        $this->emit('saved', $this->changes);
        $this->changes = array();
    }

    /**
     * Deletes the row from the database, returning a copy of the data from before deletion.
     *
     * @return array
     */
    public function delete()
    {
        $oldData = $this->storage;

        $keyName = $this->getPrimaryKey();
        $keyValue = $this->getPrimaryKeyValue();

        $builder = \DB::delete()->from($this->getTable());
        $builder->where($keyName, '=', $keyValue);
        $builder->exec();

        $this->emit('deleted');
        $this->storage = array();

        return $oldData;
    }

    public function __isset($name)
    {
        return (method_exists($this, $name) || array_key_exists($name, $this->storage));
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            $relationship = $this->$name();

            if ($relationship instanceof Relationship || $relationship instanceof QueryBuilder) {
                return $relationship->get();
            }
        }

        if (array_key_exists($name, $this->storage)) {
            $getterName = 'get' . $name;
            $value = $this->storage[$name];

            if (method_exists($this, $getterName)) {
                $value = $this->$getterName($value);
            }

            return $value;
        }

        return null;
    }

    public function __set($name, $value)
    {
        $setterName = 'set' . $name;

        if (array_key_exists($name, $this->storage)) {
            if ($value === $this->__get($name)) {
                return;
            }
        }

        if (method_exists($this, $setterName)) {
            $value = $this->$setterName($value);
        }

        $this->emit('changed', $name, $value);
        $this->changes[$name] = $value;
    }

    /**
     * Checks if this object equals another.
     *
     * @param Model $object
     * @return bool
     */
    public function equals(Model $object) {
        return ($object->id === $this->id && $object->getTable() === $this->getTable());
    }

}
