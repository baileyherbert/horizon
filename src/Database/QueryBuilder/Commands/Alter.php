<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Database\QueryBuilder\ColumnDefinition;
use Horizon\Support\Str;

class Alter implements CommandInterface {

	use Traits\HasTableOptions;

	/**
	 * @var QueryBuilder
	 */
	protected $builder;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var string[]
	 */
	protected $statements = array();

	/**
	 * Constructs a new ALTER command.
	 *
	 * @param QueryBuilder $builder
	 */
	public function __construct(QueryBuilder $builder) {
		$this->builder = $builder;
	}

	/**
	 * Compiles the query into a string.
	 *
	 * @return string
	 */
	public function compile() {
		$statements = $this->statements;

		foreach ($this->getOptions() as $option => $value) {
			$statements[] = $option . ' = ' . $value;
		}

		return Str::join(
			'ALTER TABLE',
			StringBuilder::formatTableName($this->builder->getPrefix() . $this->table),
			implode(', ', $statements)
		) . ';';
	}

	/**
	 * Compiles an array of columns into an SQL-ready, comma-delimited string list of columns.
	 *
	 * @param array $columns
	 * @return string
	 */
	protected function compileColumnList(array $columns) {
		$columnList = array();
		foreach ($columns as $colName) {
			$columnList[] = StringBuilder::formatColumnName($colName);
		}

		return implode(', ', $columnList);
	}

	/**
	 * Sets the table to alter.
	 *
	 * @param string $table
	 */
	public function table($table) {
		$this->table = $table;
		return $this;
	}

	/**
	 * Adds a column to the table.
	 *
	 * @param ColumnDefinition $column
	 * @param string|null $after
	 * @return $this
	 */
	public function addColumn(ColumnDefinition $column, $after = null) {
		$compiled = array(
			'ADD',
			$column->compile()
		);

		if (!is_null($after)) {
			$compiled[] = 'AFTER';
			$compiled[] = StringBuilder::formatColumnName($after);
		}

		$this->statements[] = Str::join($compiled);
		return $this;
	}

	/**
	 * Drops a column by its name.
	 *
	 * @param string $columnName
	 * @return $this
	 */
	public function dropColumn($columnName) {
		$this->statements[] = 'DROP ' . StringBuilder::formatColumnName($columnName);
		return $this;
	}

	/**
	 * Modifies a column and optionally changes its position.
	 *
	 * @param ColumnDefinition $column
	 * @param string|null $after
	 * @return $this
	 */
	public function modifyColumn(ColumnDefinition $column, $after = null) {
		$compiled = array(
			'MODIFY',
			$column->compile()
		);

		if (!is_null($after)) {
			$compiled[] = ($after == 'FIRST') ? 'FIRST' : 'AFTER ' . StringBuilder::formatColumnName($after);
		}

		$this->statements[] = Str::join($compiled);
		return $this;
	}

	/**
	 * Changes a column's name, schema, and optionally its position.
	 *
	 * @param ColumnDefinition $column
	 * @param string $newName
	 * @param string|null $after
	 * @return $this
	 */
	public function changeColumn(ColumnDefinition $column, $newName, $after = null) {
		$compiled = array(
			'CHANGE',
			StringBuilder::formatColumnName($column->name),
			StringBuilder::formatColumnName($newName),
			$column->compile(false)
		);

		if (!is_null($after)) {
			$compiled[] = ($after == 'FIRST') ? 'FIRST' : 'AFTER ' . StringBuilder::formatColumnName($after);
		}

		$this->statements[] = Str::join($compiled);
		return $this;
	}

	/**
	 * Adds a primary key to the table.
	 *
	 * @param string $columnName,...
	 * @return $this
	 */
	public function addPrimaryKey() {
		$this->statements[] = sprintf('ADD PRIMARY KEY (%s)', $this->compileColumnList(func_get_args()));
		return $this;
	}

	/**
	 * Adds an index to the table.
	 *
	 * @param string $columnName,...
	 * @return $this
	 */
	public function addIndex() {
		$keyName = StringBuilder::formatColumnName($this->table . '_' . implode('_', func_get_args()));

		$this->statements[] = sprintf('ADD INDEX %s (%s)', $keyName, $this->compileColumnList(func_get_args()));
		return $this;
	}

	/**
	 * Adds a unique index to the table.
	 *
	 * @param string $columnName,...
	 * @return $this
	 */
	public function addUniqueIndex() {
		$keyName = StringBuilder::formatColumnName($this->table . '_' . implode('_', func_get_args()));

		$this->statements[] = sprintf('ADD UNIQUE INDEX %s (%s)', $keyName, $this->compileColumnList(func_get_args()));
		return $this;
	}

	/**
	 * Adds a foreign key to the table.
	 *
	 * @param string|string[] $columns
	 * @param string $foreignTable
	 * @param string|string[] $foreignColumns
	 * @param string|null $onDelete
	 * @param string|null $onUpdate
	 * @return $this
	 */
	public function addForeignKey($columns, $foreignTable, $foreignColumns, $onDelete = null, $onUpdate = null) {
		if (!is_array($columns)) $column = array($columns);
		if (!is_array($foreignColumns)) $foreignColumns = array($foreignColumns);

		$keyName = StringBuilder::formatColumnName(
			'fk_' . $this->table . '_' . implode('_', $columns) .
			'_' . $foreignTable . '_' . implode('_', $foreignColumns));

		$statement = sprintf(
			'ADD FOREIGN KEY %s (%s) REFERENCES %s (%s)',
			$keyName, $this->compileColumnList($columns),
			StringBuilder::formatTableName($this->builder->getPrefix() . $foreignTable),
			$this->compileColumnList($foreignColumns)
		);

		if (!is_null($onDelete)) $statement .= ' ON DELETE ' . strtoupper($onDelete);
		if (!is_null($onUpdate)) $statement .= ' ON UPDATE ' . strtoupper($onUpdate);

		$this->statements[] = $statement;
		return $this;
	}

	/**
	 * Drops the table's current primary key.
	 *
	 * @return $this
	 */
	public function dropPrimaryKey() {
		$this->statements[] = 'DROP PRIMARY KEY';
		return $this;
	}

	/**
	 * Drops an index from the table.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function dropIndex($name) {
		$this->statements[] = 'DROP INDEX ' . StringBuilder::formatColumnName($name);
		return $this;
	}

	/**
	 * Drops a foreign key from the table.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function dropForeignKey($name) {
		$this->statements[] = 'DROP FOREIGN KEY ' . StringBuilder::formatColumnName($name);
		return $this;
	}

	/**
	 * Renames the table (prefix is added automatically).
	 *
	 * @param string $newTableName
	 * @return $this
	 */
	public function rename($newTableName) {
		$this->statements[] = 'RENAME ' . StringBuilder::formatTableName($this->builder->getPrefix() . $newTableName);
		return $this;
	}

	/**
	 * Sets the ENGINE option for the table.
	 *
	 * @param string $engine
	 * @return $this
	 */
	public function engine($engine) {
		return $this->opt('ENGINE', $engine);
	}

	/**
	 * Sets the CHARSET option for the table.
	 *
	 * @param string $charset
	 * @return $this
	 */
	public function charset($charset) {
		return $this->opt('CHARACTER SET', $charset);
	}

	/**
	 * Sets the COLLATE option for the table.
	 *
	 * @param string $collation
	 * @return $this
	 */
	public function collate($collation) {
		return $this->opt('COLLATE', $collation);
	}

	public function getParameters() {
		return array();
	}

}
