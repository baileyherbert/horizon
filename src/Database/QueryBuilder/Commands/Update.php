<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Support\Str;
use Horizon\Database\Exception\QueryBuilderException;
use Horizon\Database\QueryBuilder\ColumnReference;
use Horizon\Database\QueryBuilder\RawReference;

class Update implements CommandInterface {

	/**
	 * @var QueryBuilder
	 */
	protected $builder;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @var array The conditions to query against.
	 */
	protected $wheres = array();

	/**
	 * @var array Positions of parenthesis in the where statements.
	 */
	protected $enclosures = array();

	/**
	 * @var array Order by statements.
	 */
	protected $orders = array();

	/**
	 * @var int|null
	 */
	protected $limit;

	/**
	 * @var array
	 */
	protected $compiledParameters = array();

	/**
	 * Constructs a new instance.
	 *
	 * @param QueryBuilder $builder
	 */
	public function __construct(QueryBuilder $builder) {
		$this->builder = $builder;
	}

	/**
	 * Compiles the statement into a string.
	 *
	 * @return string
	 */
	public function compile() {
		$this->compiledParameters = array();

		return Str::join(
			'UPDATE',
			$this->compileTable(),
			'SET',
			$this->compileValues(),
			$this->compileWheres(),
			$this->compileOrderBy(),
			$this->compileLimit()
		) . ';';
	}

	/**
	 * @return string
	 */
	protected function compileTable() {
		if (!$this->table) return '';

		$prefix = $this->builder->getPrefix();
		if (!$prefix) $prefix = '';

		return StringBuilder::formatTableName($prefix . $this->table);
	}

	/**
	 * @return string
	 */
	protected function compileValues() {
		$compiled = array();

		foreach ($this->values as $key => $value) {
			if (is_array($value)) {
				$compiled[] = sprintf('%s = %s', StringBuilder::formatColumnName($key), $this->compileFunction($value));
			}
			else if ($value instanceof ColumnReference) {
				$compiled[] = sprintf(
					'%s = %s',
					StringBuilder::formatColumnName($key),
					StringBuilder::formatColumnName($value->name)
				);
			}
			else if ($value instanceof RawReference) {
				$compiled[] = sprintf(
					'%s = %s',
					StringBuilder::formatColumnName($key),
					$value->value
				);
			}
			else {
				$compiled[] = sprintf('%s = ?', StringBuilder::formatColumnName($key));
				$this->compiledParameters[] = $value;
			}
		}

		return implode(', ', $compiled);
	}

	/**
	 * @return string
	 */
	protected function compileWheres() {
		if (empty($this->wheres)) {
			return '';
		}

		$compiled = array('WHERE');

		foreach ($this->wheres as $i => $where) {
			$column = StringBuilder::formatColumnName($where['column']);
			$operator = StringBuilder::formatOperator($where['operator']);
			$separator = $where['separator'];
			$startEnclosure = $this->getEnclosureStartingAt($i);

			if ($i > 0 && !$startEnclosure) {
				$compiled[] = $separator;
			}
			elseif ($startEnclosure) {
				if ($i > 0) {
					$compiled[] = $startEnclosure['separator'];
				}

				for ($x = 0; $x < $this->getNumEnclosuresStartingAt($i); $x++) {
					$compiled[] = '(';
				}
			}

			if (isset($where['function'])) {
				$compiled[] = sprintf('%s %s %s', $column, $operator, $this->compileFunction($where['function']));
			}
			else {
				$value = $where['value'];

				if ($where['reference']) {
					$value = StringBuilder::formatColumnName($value);

					$compiled[] = sprintf('%s %s %s', $column, $operator, $value);
				}
				else {
					if (!is_null($value)) {
						$compiled[] = sprintf('%s %s ?', $column, $operator);
						$this->compiledParameters[] = $value;
					}
					else {
						$compiled[] = sprintf('%s %s NULL', $column, $operator);
					}
				}
			}

			if ($this->getEnclosureEndingAt($i)) {
				for ($x = 0; $x < $this->getNumEnclosuresEndingAt($i); $x++) {
					$compiled[] = ')';
				}
			}
		}

		return Str::join($compiled);
	}

	/**
	 * @return string
	 */
	protected function compileFunction($function) {
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
	 * @return string
	 */
	protected function compileOrderBy() {
		$compiled = array();

		if (count($this->orders) == 0) {
			return '';
		}

		if ($this->orders === 'RAND()') {
			return 'ORDER BY RAND()';
		}

		foreach ($this->orders as $order) {
			$compiled[] = sprintf(
				'%s %s',
				StringBuilder::formatColumnName($order['column']),
				StringBuilder::formatOperator($order['direction'])
			);
		}

		return 'ORDER BY ' . implode(', ', $compiled);
	}

	/**
	 * @return string
	 */
	protected function compileLimit() {
		return !is_null($this->limit) ? ('LIMIT ' . $this->limit) : '';
	}

	/**
	 * Gets details for the last enclosure at the specified index.
	 *
	 * @param int $i
	 * @return array|null
	 */
	protected function getEnclosureStartingAt($i) {
		$enclosure = null;

		foreach ($this->enclosures as $enclose) {
			if ($enclose['start'] == $i) {
				$enclosure = $enclose;
			}
		}

		return $enclosure;
	}

	/**
	 * Gets the number of enclosures at index $i.
	 *
	 * @param int $i
	 * @return int
	 */
	protected function getNumEnclosuresStartingAt($i) {
		$count = 0;

		foreach ($this->enclosures as $enclose) {
			if ($enclose['start'] == $i) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Gets details for the last enclosure ending at the specified index.
	 *
	 * @param int $i
	 * @return array|null
	 */
	protected function getEnclosureEndingAt($i) {
		$enclosure = null;

		foreach ($this->enclosures as $enclose) {
			if ($enclose['end'] == $i) {
				$enclosure = $enclose;
			}
		}

		return $enclosure;
	}

	/**
	 * Gets the number of enclosures ending at index $i.
	 *
	 * @param int $i
	 * @return int
	 */
	protected function getNumEnclosuresEndingAt($i) {
		$count = 0;

		foreach ($this->enclosures as $enclose) {
			if ($enclose['end'] == $i) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Gets an array of values to replace prepared ? characters with.
	 *
	 * @return array
	 */
	public function getParameters() {
		$this->compile();
		return $this->compiledParameters;
	}

	/**
	 * Sets the table to update.
	 *
	 * @param string $tableName
	 * @return $this
	 */
	public function table($tableName) {
		$this->table = $tableName;
		return $this;
	}

	/**
	 * Sets the values to replace.
	 *
	 * @param string $tableName
	 * @return $this
	 */
	public function values(array $values) {
		foreach ($values as $key => $val) {
			if (StringBuilder::isFunction($val) && !is_array($val)) {
				$values[$key] = array($val);
			}
		}

		$this->values = $values;
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
	public function where($column, $operator, $equals, $separator = 'AND', $reference = false) {
		$function = (is_array($equals)) ? $equals : null;

		if (is_string($equals) && StringBuilder::isFunction($equals)) {
			$function = array($equals);
		}

		$this->wheres[] = array(
			'column' => $column,
			'operator' => $operator,
			'value' => $equals,
			'separator' => $separator,
			'function' => $function,
			'reference' => $reference
		);

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
	public function andWhere($column, $operator, $equals) {
		return $this->where($column, $operator, $equals);
	}

	/**
	 * Sets a condition rows must match to be selected.
	 *
	 * @param string $column
	 * @param string $operator
	 * @param mixed $equals
	 * @return $this
	 */
	public function orWhere($column, $operator, $equals) {
		return $this->where($column, $operator, $equals, 'OR');
	}

	/**
	 * Encloses the statements created inside in parenthesis.
	 *
	 * @param callable $callback
	 * @param string $separator
	 * @return $this
	 */
	public function enclose(callable $callback, $separator = 'AND') {
		$startPosition = count($this->wheres);
		$callback($this->builder);
		$endPosition = count($this->wheres) - 1;

		$this->enclosures[] = array(
			'start' => $startPosition,
			'end' => $endPosition,
			'separator' => $separator
		);

		return $this;
	}

	/**
	 * Encloses the statements created inside in parenthesis.
	 *
	 * @param callable $callback
	 * @return $this
	 */
	public function orEnclose(callable $callback) {
		return $this->enclose($callback, 'OR');
	}

	/**
	 * Encloses the statements created inside in parenthesis.
	 *
	 * @param callable $callback
	 * @return $this
	 */
	public function andEnclose(callable $callback) {
		return $this->enclose($callback);
	}

	/**
	 * Sets the query limit.
	 *
	 * @param int $limit
	 * @return $this
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Sorts the query. Accepts unlimited arguments in pairs of two (column, direction).
	 *
	 * @param string $column
	 * @param string $direction,...
	 * @return $this
	 */
	public function orderBy() {
		$args = func_get_args();

		if (count($args) == 1) {
			if (strtolower($args[0]) == 'rand()') {
				$this->orders = 'RAND()';
				return $this;
			}
		}

		if (is_string($this->orders)) {
			throw new QueryBuilderException('Cannot define additional orders after applying RAND()');
		}

		if (count($args) % 2 !== 0) {
			throw new QueryBuilderException('orderBy() had an invalid number of arguments (expecting multiple of 2)');
		}

		while (count($args) > 0) {
			$column = array_shift($args);
			$direction = array_shift($args);

			$this->orders[] = array(
				'column' => $column,
				'direction' => $direction
			);
		}

		return $this;
	}

}
