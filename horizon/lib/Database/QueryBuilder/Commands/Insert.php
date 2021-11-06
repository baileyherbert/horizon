<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Horizon\Database\QueryBuilder;
use Horizon\Support\Str;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Support\Arr;
use Horizon\Database\Exception\QueryBuilderException;

class Insert implements CommandInterface
{

	/**
	 * @var QueryBuilder
	 */
	protected $builder;

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @var array
	 */
	protected $compiledParameters = array();

	/**
	 * @var string|null
	 */
	protected $table;

	/**
	 * Constructs a new instance.
	 *
	 * @param QueryBuilder $builder
	 */
	public function __construct(QueryBuilder $builder)
	{
		$this->builder = $builder;
	}

	/**
	 * Gets the query as a prepared string.
	 *
	 * @return string
	 */
	public function compile()
	{
		$this->compiledParameters = array();

		return Str::join(
			'INSERT INTO',
			$this->compileTable(),
			$this->compileColumns(),
			$this->compileValues()
		) . ';';
	}

	protected function compileTable()
	{
		$prefix = $this->builder->getPrefix();

		if (!$prefix) {
			$prefix = '';
		}

		return StringBuilder::formatTableName($prefix . $this->table);
	}

	protected function compileColumns()
	{
		$columns = $this->getColumns();
		$compiled = array();

		foreach ($columns as $col) {
			$compiled[] = StringBuilder::formatColumnName($col);
		}

		return '(' . implode(', ', $compiled) . ')';
	}

	protected function compileValues()
	{
		$columns = $this->getColumns();
		$compiled = array();

		if (empty($columns)) {
			return '';
		}

		foreach ($this->values as $row) {
			$rowValues = array();

			foreach ($columns as $col) {
				$value = $row[$col];

				if (!is_array($value)) {
					$this->compiledParameters[] = $value;
					$rowValues[] = '?';
				}
				else {
					$rowValues[] = $this->compileFunction($value);
				}
			}

			$compiled[] = sprintf('(%s)', implode(', ', $rowValues));
		}

		return sprintf('VALUES %s', implode(', ', $compiled));
	}

	protected function compileFunction($function)
	{
		$functionString = array_shift($function);

		if (!preg_match('/^([A-Z]+)(\([^)]*\))$/', $functionString, $matches)) {
			throw new QueryBuilderException('Failed to compile function ' . $functionString);
		}

		$functionName = StringBuilder::formatOperator($matches[1]);
		$args = $function;
		$compiledArgs = array();

		foreach ($args as $arg) {
			$compiledArgs[] = '?';
			$this->compiledParameters[] = $arg;
		}

		return sprintf('%s(%s)', $functionName, implode(', ', $compiledArgs));
	}

	/**
	 * Gets an array of values to replace prepared ? characters with.
	 *
	 * @return array
	 */
	public function getParameters()
	{
		$this->compile();
		return $this->compiledParameters;
	}

	/**
	 * Gets the columns used in inserted values.
	 *
	 * @return array
	 */
	protected function getColumns()
	{
		$columns = array();

		foreach ($this->values as $row) {
			foreach ($row as $column => $value) {
				if (!in_array($column, $columns)) {
					$columns[] = $column;
				}
			}
		}

		return $columns;
	}

	public function into($tableName)
	{
		$this->table = $tableName;
		return $this;
	}

	public function values(array $values = array())
	{
		if (func_num_args() > 1) {
			$values = func_get_args();
		}

		$rows = $this->prepareValues($values);

		foreach ($rows as $rowIndex => $row) {
			foreach ($row as $col => $val) {
				if (StringBuilder::isFunction($val) && !is_array($val)) {
					$rows[$rowIndex][$col] = array($val);
				}
			}
		}

		$this->values = $rows;

		return $this;
	}

	protected function prepareValues(array &$values)
	{
		if (count($values) > 0) {
			if (isset($values[0]) && is_array($values[0])) {
				return $values;
			}
			else {
				return array($values);
			}
		}

		return array();
	}

}
