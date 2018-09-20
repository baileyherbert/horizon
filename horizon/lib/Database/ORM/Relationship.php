<?php

namespace Horizon\Database\ORM;

use Horizon\Database\Model;
use Horizon\Database\QueryBuilder\Documentation\SelectHelper;

class Relationship extends SelectHelper
{

    /**
     * @var SelectHelper
     */
    protected $query;

    /**
     * Sets a condition rows must match to be selected.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $equals
     * @return $this
     */
    public function where()
    {
        call_user_func_array(array($this->query, 'where'), func_get_args());
        return $this;
    }

    /**
     * Sets a condition rows must match to be selected.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $equals
     * @return $this
     */
    public function andWhere()
    {
        call_user_func_array(array($this->query, 'andWhere'), func_get_args());
        return $this;
    }

    /**
     * Sets a condition rows must match to be selected.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $equals
     * @return $this
     */
    public function orWhere()
    {
        call_user_func_array(array($this->query, 'orWhere'), func_get_args());
        return $this;
    }

    /**
     * Encloses the statements created inside in parenthesis.
     *
     * @param callable $callback
     * @param string $separator
     * @return $this
     */
    public function enclose()
    {
        call_user_func_array(array($this->query, 'enclose'), func_get_args());
        return $this;
    }

    /**
     * Encloses the statements created inside in parenthesis.
     *
     * @param callable $callback
     * @return $this
     */
    public function orEnclose()
    {
        call_user_func_array(array($this->query, 'orEnclose'), func_get_args());
        return $this;
    }

    /**
     * Encloses the statements created inside in parenthesis.
     *
     * @param callable $callback
     * @return $this
     */
    public function andEnclose()
    {
        call_user_func_array(array($this->query, 'andEnclose'), func_get_args());
        return $this;
    }

    /**
     * Sets whether the query is distinct.
     *
     * @param bool $is
     * @return $this
     */
    public function distinct()
    {
        call_user_func_array(array($this->query, 'distinct'), func_get_args());
        return $this;
    }

    /**
     * Sets the query limit.
     *
     * @param int $limit
     * @return $this
     */
    public function limit()
    {
        call_user_func_array(array($this->query, 'limit'), func_get_args());
        return $this;
    }

    /**
     * Sets the query limit offset.
     *
     * @param int $start
     * @return $this
     */
    public function offset()
    {
        call_user_func_array(array($this->query, 'offset'), func_get_args());
        return $this;
    }

    /**
     * Sorts the query. Accepts unlimited arguments in pairs of two (column, direction).
     *
     * @param string $column
     * @param string $direction,...
     * @return $this
     */
    public function orderBy()
    {
        call_user_func_array(array($this->query, 'orderBy'), func_get_args());
        return $this;
    }

}