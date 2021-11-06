<?php

namespace Horizon\Database\Migration\Schema;

use Horizon\Database\Migration\Blueprint;

/**
 * Represents a database table column.
 */
class Column {

	/**
	 * The blueprinted type of the column (will not always be the same as the SQL type).
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The name of the column.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The new name of the column if it needs to be changed.
	 *
	 * @var string|null
	 */
	private $newName;

	/**
	 * The parameters of this column.
	 *
	 * @var array
	 */
	private $parameters;

	/**
	 * The blueprint instance for this column.
	 *
	 * @var Blueprint
	 */
	private $table;

	/**
	 * Determines if the column can be set to null.
	 *
	 * @var bool
	 */
	private $nullable = false;

	/**
	 * Determines if the column is auto-incrementing (primary keys only).
	 *
	 * @var bool
	 */
	private $autoIncrementing;

	/**
	 * Determines the column's default value.
	 *
	 * @var mixed
	 */
	private $defaultValue;

	/**
	 * Determines if the column should apply its default value.
	 *
	 * @var bool
	 */
	private $defaultValueSet = false;

	/**
	 * Determines the column's comment.
	 *
	 * @var string|null
	 */
	private $comment;

	/**
	 * Determines the column's character set.
	 *
	 * @var string|null
	 */
	private $charset;

	/**
	 * Determines the column's collation.
	 *
	 * @var string|null
	 */
	private $collation;

	/**
	 * Whether this is an existing column that needs updating.
	 *
	 * @var bool
	 */
	private $change = false;

	/**
	 * Where to position the column ('FIRST' or 'AFTER column').
	 *
	 * @var string|null
	 */
	private $placement;

	/**
	 * Constructs a new column instance.
	 *
	 * @param string $type
	 * @param string $name
	 * @param array $parameters
	 * @param Blueprint $table
	 */
	public function __construct($type, $name, array $parameters, Blueprint $table) {
		$this->type = $type;
		$this->name = $name;
		$this->parameters = $parameters;
		$this->table = $table;
	}

	/**
	 * Place the column "after" another column.
	 * @param string $column
	 * @return $this
	 */
	public function after($column) {
		$this->placement = 'AFTER ' . Grammar::compileName($column);

		return $this;
	}

	/**
	 * Set INTEGER columns as auto-increment.
	 *
	 * @return $this
	 */
	public function autoIncrement() {
		$this->autoIncrementing = true;

		return $this;
	}

	/**
	 * Marks the column as modified so the migration will update it.
	 *
	 * @return $this
	 */
	public function change() {
		$this->change = true;

		return $this;
	}

	/**
	 * Specify a character set for the column.
	 *
	 * @param string $charset
	 * @return $this
	 */
	public function charset($charset) {
		$this->charset = $charset;

		return $this;
	}

	/**
	 * Specify a collation for the column.
	 *
	 * @param string $collation
	 * @return $this
	 */
	public function collation($collation) {
		$this->collation = $collation;

		return $this;
	}

	/**
	 * Add a comment to the column.
	 *
	 * @param string $comment
	 * @return $this
	 */
	public function comment($comment) {
		$this->comment = $comment;

		return $this;
	}

	/**
	 * Set the value that the column defaults to.
	 *
	 * @param mixed $value
	 * @return $this
	 */
	public function defaults($value) {
		$this->defaultValue = $value;
		$this->defaultValueSet = true;

		return $this;
	}

	/**
	 * Place the column "first" in the table
	 *
	 * @return $this
	 */
	public function first() {
		$this->placement = 'FIRST';

		return $this;
	}

	/**
	 * Add an index.
	 *
	 * @return $this
	 */
	public function index() {
		$this->table->index($this->name);

		return $this;
	}

	/**
	 * Allow NULL values to be inserted into the column.
	 *
	 * @param bool $value
	 * @return $this
	 */
	public function nullable($value = true) {
		$this->nullable = $value;

		return $this;
	}

	/**
	 * Add a primary index.
	 *
	 * @return $this
	 */
	public function primary() {
		$this->table->primary($this->name);

		return $this;
	}

	/**
	 * Changes the name of the column. Remember to call change() as well for this to take effect.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function rename($name) {
		$this->newName = $name;
		$this->change = true;

		return $this;
	}

	/**
	 * Add a unique index.
	 *
	 * @return $this
	 */
	public function unique() {
		$this->table->unique($this->name);

		return $this;
	}

	/**
	 * Set the INTEGER column as UNSIGNED.
	 *
	 * @return $this
	 */
	public function unsigned() {
		$this->parameters['unsigned'] = true;

		return $this;
	}

	/**
	 * Set the TIMESTAMP column to use CURRENT_TIMESTAMP as default value.
	 *
	 * @return $this
	 */
	public function useCurrent() {
		$this->defaults('CURRENT_TIMESTAMP');

		return $this;
	}

	/**
	 * Compiles the column into a column definition.
	 *
	 * @return string
	 */
	public function __toString() {
		$statement = '';

		if (!$this->table->isCreating()) {
			if ($this->change) {
				$statement = is_null($this->newName) ? 'MODIFY' : 'CHANGE';
			}
			else {
				$statement = 'ADD';
			}
		}

		return str_join(
			$statement,
			Grammar::compileName($this->name),
			(!is_null($this->newName) && $this->change) ? Grammar::compileName($this->newName) : '',
			$this->compileDataType(),
			$this->compileCollate(),
			$this->compileNullable(),
			$this->compileDefault(),
			$this->compileIncrements(),
			$this->compileComment(),
			(!is_null($this->placement) && $this->change) ? $this->placement : ''
		);
	}

	/**
	 * Compiles the data type. For example, "INT" or "CHAR(32)" or "DECIMAL(8, 2)".
	 *
	 * @return string
	 */
	private function compileDataType() {
		$type = Grammar::getColumnType($this->type);

		// Add (total, places) values for floating point columns
		if (Grammar::isFloatingPoint($this->type)) {
			$total = $this->parameters['total'];
			$places = $this->parameters['places'];

			if (!is_null($total) && !is_null($places)) {
				$type .= sprintf('(%d, %d)', $total, $places);
			}
		}

		// Add (length) value for variable-length textual columns
		else if (Grammar::isVariableLength($this->type)) {
			$type .= sprintf('(%d)', $this->parameters['length']);
		}

		// Add the unsigned attribute
		if (isset($this->parameters['unsigned']) && $this->parameters['unsigned']) {
			$type .= ' UNSIGNED';
		}

		return $type;
	}

	/**
	 * Compiles the nullable definition. For example, "NULL" or "NOT NULL".
	 *
	 * @return string
	 */
	private function compileNullable() {
		return $this->nullable ? 'NULL' : 'NOT NULL';
	}

	/**
	 * Compiles the default value definition. For example, "DEFAULT NULL" or "DEFAULT 0".
	 * This supports booleans, integers, floats, text, dates, and CURRENT_TIMESTAMP.
	 *
	 * @return string
	 */
	private function compileDefault() {
		if ($this->defaultValueSet) {
			return 'DEFAULT ' . Grammar::compileDefault($this->defaultValue);
		}

		return '';
	}

	/**
	 * Compiles the auto incrementing definition for applicable columns.
	 *
	 * @return string
	 */
	private function compileIncrements() {
		if ($this->autoIncrementing || array_get($this->parameters, 'autoIncrement', false)) {
			return 'AUTO_INCREMENT';
		}

		return '';
	}

	/**
	 * Compiles the auto incrementing definition for applicable columns.
	 *
	 * @return string
	 */
	private function compileComment() {
		if (is_string($this->comment)) {
			return 'COMMENT ' . Grammar::compileComment($this->comment);
		}

		return '';
	}

	/**
	 * Compiles the character collation definition.
	 *
	 * @return string
	 */
	private function compileCollate() {
		$charset = '';
		$collate = '';

		if (!is_null($this->charset)) $charset = 'CHARACTER SET ' . $this->charset;
		if (!is_null($this->collation)) $collate = 'COLLATE ' . $this->collation;

		return str_join($charset, $collate);
	}

	/**
	 * Gets a private variable from the column (read-only).
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		if (isset($this->$name)) {
			return $this->$name;
		}

		return null;
	}

}
