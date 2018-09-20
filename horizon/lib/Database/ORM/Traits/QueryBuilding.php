<?php

namespace Horizon\Database\ORM\Traits;

use DB;
use Horizon\Database\QueryBuilder\Documentation\SelectHelper;
use Horizon\Database\QueryBuilder\Documentation\UpdateHelper;
use Horizon\Http\Exception\HttpResponseException;

trait QueryBuilding
{

    /**
     * Gets all rows. Careful!
     *
     * @return SelectHelper
     */
    public static function all()
    {
        $o = (new static);

        $builder = DB::select()->from($o->getTable());
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
    public static function where($column, $operator, $equals)
    {
        $o = (new static);

        $builder = DB::select()->from($o->getTable())->where($column, $operator, $equals);
        $builder->setModel(get_class($o));

        return $builder;
    }

    /**
     * Gets a model instance by the primary key value.
     *
     * @param int $primaryKey
     * @return static
     */
    public static function find($primaryKey)
    {
        $o = (new static);

        $table = $o->getTable();
        $keyName = $o->getPrimaryKey();

        $builder = DB::select()->from($table)->where($keyName, '=', $primaryKey)->limit(1);
        $builder->setModel(get_class($o));

        return $builder->first();
    }

    /**
     * Gets a model instance by the primary key value. If not found, it raises an HttpResponseException with the
     * provided code (or default 404).
     *
     * @param int $primaryKey
     * @param int $code
     * @return static
     */
    public static function findOrFail($primaryKey, $code = 404)
    {
        $o = (new static);

        $table = $o->getTable();
        $keyName = $o->getPrimaryKey();

        $builder = DB::select()->from($table)->where($keyName, '=', $primaryKey)->limit(1);
        $builder->setModel(get_class($o));

        $model = $builder->first();

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
    public static function create(array $values)
    {
        $o = (new static);

        $table = $o->getTable();
        $keyName = $o->getPrimaryKey();

        $builder = DB::insert()->into($table)->values($values);
        $id = $builder->exec();

        if (!$id) {
            return null;
        }

        return static::find($id);
    }

}
