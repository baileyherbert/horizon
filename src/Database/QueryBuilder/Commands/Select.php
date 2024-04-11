<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Exception;
use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Support\Str;
use Horizon\Database\Exception\QueryBuilderException;

class Select implements CommandInterface {

	/**
	 * @var QueryBuilder
	 */
	protected $builder;

	/**
	 * @var string[] The tables to query from.
	 */
	protected $tables = array();

	/**
	 * @var array The tables to join.
	 */
	protected $joins = array();

	/**
	 * @var string[] The columns to select.
	 */
	protected $columns = array();

	/**
	 * @var array The conditions to query against.
	 */
	protected $wheres = array();

	/**
	 * @var array Fulltext searches to calculate the relevancy scores with.
	 */
	protected $scoreColumns = array();

	/**
	 * @var array Positions of parenthesis in the where statements.
	 */
	protected $enclosures = array();

	/**
	 * @var array Order by statements.
	 */
	protected $orders = array();

	/**
	 * @var bool Whether the query is distinct or not.
	 */
	protected $distinct = false;

	/**
	 * @var int|null
	 */
	protected $limit;

	/**
	 * @var int|null
	 */
	protected $offset;

	/**
	 * @var string
	 */
	protected $for = '';

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
		$this->columns = array('*');
	}

	/**
	 * Compiles the statement into a string.
	 *
	 * @return string
	 */
	public function compile() {
		$this->compiledParameters = array();

		return Str::join(
			$this->compileCommand(),
			$this->compileColumns(),
			$this->compileTables(),
			$this->compileJoins(),
			$this->compileWheres(),
			$this->compileOrderBy(),
			$this->compileLimit(),
			$this->for
		) . ';';
	}

	/**
	 * @return string
	 */
	protected function compileCommand() {
		return 'SELECT' . ($this->distinct ? ' DISTINCT' : '');
	}

	/**
	 * @return string
	 */
	protected function compileColumns() {
		$compiled = array();

		if (count($this->columns) == 1 && $this->columns[0] == 'COUNT(*)') {
			return 'COUNT(*)';
		}

		foreach ($this->columns as $name) {
			$compiled[] = StringBuilder::formatColumnName($name);
		}

		return implode(', ', $compiled);
	}

	/**
	 * @return string
	 */
	protected function compileTables() {
		$compiled = array();

		if (count($this->tables) == 0) {
			return '';
		}

		foreach ($this->tables as $name) {
			$prefix = $this->builder->getPrefix();
			$table = $prefix ? $prefix . $name : $name;

			$compiled[] = StringBuilder::formatTableName($table);
		}

		return 'FROM ' . implode(', ', $compiled);
	}

	/**
	 * @return string
	 */
	protected function compileJoins() {
		$compiled = array();

		foreach ($this->joins as $join) {
			$compiled[] = $join['type'] . ' JOIN';
			$compiled[] = StringBuilder::formatTableName($join['tableName']);
			$alias = $join['tableAlias'];

			if (is_null($alias)) {
				$alias = $join['tableName'];
			}

			$compiled[] = StringBuilder::formatTableName($alias);

			if (!is_null($join['leftColumn'])) {
				$columnLeft = StringBuilder::formatColumnName($join['leftColumn']);
				$columnRight = StringBuilder::formatColumnName($join['rightColumn']);

				$compiled[] = sprintf('ON %s = %s', $columnLeft, $columnRight);
			}
		}

		return implode(' ', $compiled);
	}

	/**
	 * Joins another table into the query.
	 *
	 * @param string $type The join type (such as `INNER` or `LEFT`).
	 * @param string|null $tableName The name of the table to join.
	 * @param string|null $tableAlias The alias to use for the table. If not specified, defaults to the table name itself.
	 * @param string|null $leftColumn The left column to use for the ON equality condition, in the format `table.column`.
	 * @param string|null $rightColumn The right column to use for the ON equality condition, in the format `table.column`.
	 * @return $this
	 */
	public function join($type, $tableName, $tableAlias = null, $leftColumn = null, $rightColumn = null) {
		if (is_null($leftColumn) != is_null($rightColumn)) {
			throw new Exception('Cannot create a join statement because one column is null when both columns must be either null or specified');
		}

		$this->joins[] = array(
			'type' => $type,
			'tableName' => $tableName,
			'tableAlias' => $tableAlias,
			'leftColumn' => $leftColumn,
			'rightColumn' => $rightColumn
		);

		return $this;
	}

	/**
	 * Joins another table into the query with an `INNER JOIN` statement.
	 *
	 * @param string $tableName The name of the table to join.
	 * @param string|null $tableAlias The alias to use for the table. If not specified, defaults to the table name itself.
	 * @param string|null $leftColumn The left column to use for the ON equality condition, in the format `table.column`.
	 * @param string|null $rightColumn The right column to use for the ON equality condition, in the format `table.column`.
	 * @return $this
	 */
	public function innerJoin($tableName, $tableAlias = null, $leftColumn = null, $rightColumn = null) {
		if (is_null($leftColumn) != is_null($rightColumn)) {
			throw new Exception('Cannot create an INNER JOIN statement because one column is null when both columns must be either null or specified');
		}

		$this->joins[] = array(
			'type' => 'INNER',
			'tableName' => $tableName,
			'tableAlias' => $tableAlias,
			'leftColumn' => $leftColumn,
			'rightColumn' => $rightColumn
		);

		return $this;
	}

	/**
	 * Joins another table into the query with a `LEFT JOIN` statement.
	 *
	 * @param string $tableName The name of the table to join.
	 * @param string|null $tableAlias The alias to use for the table. If not specified, defaults to the table name itself.
	 * @param string $leftColumn The left column to use for the ON equality condition, in the format `table.column`.
	 * @param string $rightColumn The right column to use for the ON equality condition, in the format `table.column`.
	 * @return $this
	 */
	public function leftJoin($tableName, $tableAlias = null, $leftColumn = null, $rightColumn = null) {
		if (is_null($leftColumn) || is_null($rightColumn)) {
			throw new Exception('Cannot create a LEFT JOIN statement because one or more columns in the condition are null');
		}

		$this->joins[] = array(
			'type' => 'LEFT',
			'tableName' => $tableName,
			'tableAlias' => $tableAlias,
			'leftColumn' => $leftColumn,
			'rightColumn' => $rightColumn
		);

		return $this;
	}

	/**
	 * Joins another table into the query with a `RIGHT JOIN` statement.
	 *
	 * @param string $tableName The name of the table to join.
	 * @param string|null $tableAlias The alias to use for the table. If not specified, defaults to the table name itself.
	 * @param string $leftColumn The left column to use for the ON equality condition, in the format `table.column`.
	 * @param string $rightColumn The right column to use for the ON equality condition, in the format `table.column`.
	 * @return $this
	 */
	public function rightJoin($tableName, $tableAlias = null, $leftColumn = null, $rightColumn = null) {
		if (is_null($leftColumn) || is_null($rightColumn)) {
			throw new Exception('Cannot create a RIGHT JOIN statement because one or more columns in the condition are null');
		}

		$this->joins[] = array(
			'type' => 'RIGHT',
			'tableName' => $tableName,
			'tableAlias' => $tableAlias,
			'leftColumn' => $leftColumn,
			'rightColumn' => $rightColumn
		);

		return $this;
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

			if (isset($where['fulltext'])) {
				$compiled[] = sprintf('MATCH(%s) AGAINST (%s IN %s MODE)', StringBuilder::formatColumnName($where['column']), '?', $where['mode']);
				$this->compiledParameters[] = $where['against'];

				if ($this->getEnclosureEndingAt($i)) {
					for ($x = 0; $x < $this->getNumEnclosuresEndingAt($i); $x++) {
						$compiled[] = ')';
					}
				}

				continue;
			}

			$operator = StringBuilder::formatOperator($where['operator']);

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

		if ($this->orders === 'RAND()') {
			return 'ORDER BY RAND()';
		}

		if (count($this->orders) == 0) {
			return '';
		}

		foreach ($this->orders as $order) {
			if (isset($order['match'])) {
				$scoreColumn = null;

				if (isset($order['index'])) {
					$index = intval($order['index']);
					$scoreColumn = $this->scoreColumns[$index];
				}
				else {
					$scoreColumn = $this->scoreColumns[0];
				}

				$compiled[] = sprintf(
					'(MATCH(%s) AGAINST (%s IN %s MODE)) %s',
					StringBuilder::formatColumnName($scoreColumn['column']),
					'?',
					$scoreColumn['mode'],
					StringBuilder::formatOperator($order['direction'])
				);
				$this->compiledParameters[] = $scoreColumn['against'];

				continue;
			}

			if (isset($order['matches'])) {
				$scores = array();

				foreach ($this->scoreColumns as $where) {
					$scores[] = sprintf(
						'MATCH(%s) AGAINST (%s IN %s MODE)',
						StringBuilder::formatColumnName($where['column']),
						'?',
						$where['mode']
					);

					$this->compiledParameters[] = $where['against'];
				}

				$compiled[] = sprintf(
					'%s(%s) %s',
					StringBuilder::formatOperator($order['verb']),
					implode(', ', $scores),
					StringBuilder::formatOperator($order['direction'])
				);

				continue;
			}

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
		if (is_null($this->limit)) {
			return '';
		}

		$compiled = array('LIMIT', $this->limit);

		if (!is_null($this->offset)) {
			$compiled[] = 'OFFSET';
			$compiled[] = $this->offset;
		}

		return Str::join($compiled);
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
	 * Sets the table(s) to select from.
	 *
	 * @param string $tableName,...
	 * @return $this
	 */
	public function from() {
		$this->tables = func_get_args();
		return $this;
	}

	/**
	 * Sets the column(s) to select from.
	 *
	 * @param string $columnName,...
	 * @return $this
	 */
	public function columns() {
		$this->columns = func_get_args();
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
	 * Sets a condition rows must match to be selected.
	 *
	 * @param string $column
	 * @param string $against
	 * @param string $mode
	 * @return $this
	 */
	public function whereMatch($column, $against, $mode = 'boolean', $separator = 'AND') {
		$this->wheres[] = array(
			'fulltext' => true,
			'column' => $column,
			'against' => $against,
			'mode' => strtoupper($mode),
			'separator' => $separator,
			'reference' => false
		);

		$this->scoreColumns[] = $this->wheres[count($this->wheres) - 1];

		return $this;
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
	 * Sets whether the query is distinct.
	 *
	 * @param bool $is
	 * @return $this
	 */
	public function distinct($is = true) {
		$this->distinct = $is;
		return $this;
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
	 * Sets the query limit offset.
	 *
	 * @param int $start
	 * @return $this
	 */
	public function offset($start) {
		$this->offset = $start;
		return $this;
	}

	/**
	 * Locks the selected rows with `FOR UPDATE`.
	 *
	 * @return $this
	 */
	public function forUpdate() {
		$this->for = 'FOR UPDATE';
		return $this;
	}

	/**
	 * Locks the selected rows with `FOR SHARE`.
	 *
	 * @return $this
	 */
	public function forShare() {
		$this->for = 'FOR SHARE';
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

	public function orderByMatch($direction = 'ASC', $index = null) {
		$this->orders[] = array(
			'match' => true,
			'index' => $index,
			'direction' => $direction
		);
	}

	public function orderByMatches($direction = 'ASC', $verb = 'GREATEST') {
		$this->orders[] = array(
			'matches' => true,
			'verb' => $verb,
			'direction' => $direction
		);
	}

}
