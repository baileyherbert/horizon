<?php

namespace Horizon\Database\Migration;

use Horizon\Database\Exception\MigrationException;
use Horizon\Database\Migration\Schema\Column;
use Horizon\Database\Migration\Schema\Command;
use Horizon\Database\Migration\Schema\Grammar;

/**
 * Represents a database table.
 */
class Blueprint {

	/**
	 * The storage engine that should be used for the table.
	 *
	 * @var string
	 */
	public $engine;

	/**
	 * The default character set that should be used for the table.
	 */
	public $charset;

	/**
	 * The collation that should be used for the table.
	 */
	public $collation;

	/**
	 * @var Schema
	 */
	private $schema;

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var bool
	 */
	private $create = false;

	/**
	 * @var Column[]
	 */
	private $columns = array();

	/**
	 * @var Command[]
	 */
	private $commands = array();

	/**
	 * Blueprint constructor.
	 *
	 * @param string $name
	 * @param SchemaConnection $schema
	 */
	public function __construct($name, SchemaConnection $schema) {
		$this->table = $name;
		$this->schema = $schema;
	}

	/**
	 * Create a new auto-incrementing integer (4-byte) column on the table as a primary key.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function increments($column) {
		return $this->unsignedInteger($column, true)->primary();
	}

	/**
	 * Create a new auto-incrementing tiny integer (1-byte) column on the table as a primary key.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function tinyIncrements($column) {
		return $this->unsignedTinyInteger($column, true)->primary();
	}

	/**
	 * Create a new auto-incrementing small integer (2-byte) column on the table as a primary key.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function smallIncrements($column) {
		return $this->unsignedSmallInteger($column, true)->primary();
	}

	/**
	 * Create a new auto-incrementing medium integer (3-byte) column on the table as a primary key.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function mediumIncrements($column) {
		return $this->unsignedMediumInteger($column, true)->primary();
	}

	/**
	 * Create a new auto-incrementing big integer (8-byte) column on the table as a primary key.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function bigIncrements($column) {
		return $this->unsignedBigInteger($column, true)->primary();
	}

	/**
	 * Create a new char column on the table.
	 *
	 * @param string $column
	 * @param int $length
	 * @return Column
	 */
	public function char($column, $length = null) {
		$length = $length ?: 256;
		return $this->addColumn('char', $column, compact('length'));
	}

	/**
	 * Create a new string column on the table.
	 *
	 * @param string $column
	 * @param int $length
	 * @return Column
	 */
	public function string($column, $length = null) {
		$length = $length ?: 256;
		return $this->addColumn('string', $column, compact('length'));
	}

	/**
	 * Create a new string column on the table with a fixed length of 36.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function uuid($column) {
		$length = 36;
		return $this->addColumn('string', $column, compact('length'));
	}

	/**
	 * Create a new text column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function text($column) {
		return $this->addColumn('text', $column);
	}

	/**
	 * Create a new medium text column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function mediumText($column) {
		return $this->addColumn('mediumText', $column);
	}

	/**
	 * Create a new long text column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function longText($column) {
		return $this->addColumn('longText', $column);
	}

	/**
	 * Create a new tiny blob column on the table (255 bytes).
	 *
	 * @param string $column
	 * @return Column
	 */
	public function tinyBlob($column) {
		return $this->addColumn('tinyBlob', $column);
	}

	/**
	 * Create a new blob column on the table (64 KiB).
	 *
	 * @param string $column
	 * @return Column
	 */
	public function blob($column) {
		return $this->addColumn('blob', $column);
	}

	/**
	 * Create a new medium blob column on the table (16 MiB).
	 *
	 * @param string $column
	 * @return Column
	 */
	public function mediumBlob($column) {
		return $this->addColumn('mediumBlob', $column);
	}

	/**
	 * Create a new long blob column on the table (4 GiB).
	 *
	 * @param string $column
	 * @return Column
	 */
	public function longBlob($column) {
		return $this->addColumn('longBlob', $column);
	}

	/**
	 * Create a new integer (4-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @param bool $unsigned
	 * @return Column
	 */
	public function integer($column, $autoIncrement = false, $unsigned = false) {
		return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
	}

	/**
	 * Create a new tiny integer (1-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @param bool $unsigned
	 * @return Column
	 */
	public function tinyInteger($column, $autoIncrement = false, $unsigned = false) {
		return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
	}

	/**
	 * Create a new small integer (2-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @param bool $unsigned
	 * @return Column
	 */
	public function smallInteger($column, $autoIncrement = false, $unsigned = false) {
		return $this->addColumn('smallInteger', $column, compact('autoIncrement', 'unsigned'));
	}

	/**
	 * Create a new medium integer (3-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @param bool $unsigned
	 * @return Column
	 */
	public function mediumInteger($column, $autoIncrement = false, $unsigned = false) {
		return $this->addColumn('mediumInteger', $column, compact('autoIncrement', 'unsigned'));
	}

	/**
	 * Create a new big integer (8-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @param bool $unsigned
	 * @return Column
	 */
	public function bigInteger($column, $autoIncrement = false, $unsigned = false) {
		return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
	}

	/**
	 * Create a new unsigned integer (4-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @return Column
	 */
	public function unsignedInteger($column, $autoIncrement = false) {
		return $this->integer($column, $autoIncrement, true);
	}

	/**
	 * Create a new unsigned tiny integer (1-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @return Column
	 */
	public function unsignedTinyInteger($column, $autoIncrement = false) {
		return $this->tinyInteger($column, $autoIncrement, true);
	}

	/**
	 * Create a new unsigned small integer (2-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @return Column
	 */
	public function unsignedSmallInteger($column, $autoIncrement = false) {
		return $this->smallInteger($column, $autoIncrement, true);
	}

	/**
	 * Create a new unsigned medium integer (3-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @return Column
	 */
	public function unsignedMediumInteger($column, $autoIncrement = false) {
		return $this->mediumInteger($column, $autoIncrement, true);
	}

	/**
	 * Create a new unsigned big integer (8-byte) column on the table.
	 *
	 * @param string $column
	 * @param bool $autoIncrement
	 * @return Column
	 */
	public function unsignedBigInteger($column, $autoIncrement = false) {
		return $this->bigInteger($column, $autoIncrement, true);
	}

	/**
	 * Create a new float column on the table.
	 *
	 * @param string $column
	 * @param int $total
	 * @param int $places
	 * @return Column
	 */
	public function float($column, $total = 8, $places = 2) {
		return $this->addColumn('float', $column, compact('total', 'places'));
	}

	/**
	 * Create a new double column on the table.
	 *
	 * @param string $column
	 * @param int|null $total
	 * @param int|null $places
	 * @return Column
	 */
	public function double($column, $total = null, $places = null) {
		return $this->addColumn('double', $column, compact('total', 'places'));
	}

	/**
	 * Create a new decimal column on the table.
	 *
	 * @param string $column
	 * @param int $total
	 * @param int $places
	 * @return Column
	 */
	public function decimal($column, $total = 8, $places = 2) {
		return $this->addColumn('decimal', $column, compact('total', 'places'));
	}

	/**
	 * Create a new unsigned decimal column on the table.
	 *
	 * @param string $column
	 * @param int $total
	 * @param int $places
	 * @return Column
	 */
	public function unsignedDecimal($column, $total = 8, $places = 2) {
		return $this->addColumn('decimal', $column, array(
			'total' => $total, 'places' => $places, 'unsigned' => true,
		));
	}

	/**
	 * Create a new boolean column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function boolean($column) {
		return $this->addColumn('boolean', $column);
	}

	/**
	 * Create a new date column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function date($column) {
		return $this->addColumn('date', $column);
	}

	/**
	 * Create a new date-time column on the table.
	 *
	 * @param string $column
	 * @param int $precision
	 * @return Column
	 */
	public function dateTime($column, $precision = 0) {
		return $this->addColumn('dateTime', $column, compact('precision'));
	}

	/**
	 * Create a new time column on the table.
	 *
	 * @param string $column
	 * @param int $precision
	 * @return Column
	 */
	public function time($column, $precision = 0) {
		return $this->addColumn('time', $column, compact('precision'));
	}

	/**
	 * Create a new timestamp column on the table.
	 *
	 * @param string $column
	 * @param int $precision
	 * @return Column
	 */
	public function timestamp($column, $precision = 0) {
		return $this->addColumn('timestamp', $column, compact('precision'));
	}

	/**
	 * Create a new year column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function year($column) {
		return $this->addColumn('year', $column);
	}

	/**
	 * Create a new json column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function json($column) {
		return $this->addColumn('json', $column);
	}

	/**
	 * Create a new binary column on the table.
	 *
	 * @param string $column
	 * @return Column
	 */
	public function binary($column) {
		return $this->addColumn('binary', $column);
	}

	/**
	 * Creates the `created_at` and `updated_at` columns on the table. Both will be set to the current timestamp at
	 * row creation, and the `updated_at` column will automatically update to the current time when the row is changed.
	 *
	 * @return void
	 */
	public function timestamps() {
		$this->timestamp('created_at')->useCurrent();
		$this->timestamp('updated_at')->useCurrent(true);
	}

	/**
	 * Determine if the blueprint has a create command.
	 *
	 * @return bool
	 */
	protected function creating() {
		return $this->create;
	}

	/**
	 * Indicate that the table needs to be created.
	 *
	 * @return void
	 */
	public function create() {
		$this->create = true;
	}

	/**
	 * Indicate that the given columns should be dropped.
	 *
	 * @param array|mixed $columns
	 * @return Command
	 * @throws
	 */
	public function dropColumn($columns) {
		if ($this->isCreating()) {
			throw new MigrationException('Cannot drop a column while in table creation mode.');
		}

		$columns = is_array($columns) ? $columns : func_get_args();
		return $this->addCommand('dropColumn', compact('columns'));
	}

	/**
	 * Indicate that the given columns should be renamed.
	 *
	 * @param string $from
	 * @param string $to
	 * @return $this
	 * @throws
	 */
	public function renameColumn($from, $to) {
		if ($this->isCreating()) {
			throw new MigrationException('Cannot rename a column while in table creation mode.');
		}

		foreach ($this->columns as $column) {
			if ($column->name == $from) {
				$column->rename($to);
				$column->change();
			}
		}

		return $this;
	}

	/**
	 * Specify the primary key(s) for the table.
	 *
	 * @param string|array $columns
	 * @param string $name
	 * @param string|null $algorithm
	 * @return Command
	 */
	public function primary($columns, $name = null, $algorithm = null) {
		return $this->indexCommand('primary', $columns, $name, $algorithm);
	}

	/**
	 * Indicate that the given primary key should be dropped.
	 *
	 * @param string|array $index
	 * @return Command
	 * @throws
	 */
	public function dropPrimary($index = null) {
		return $this->dropIndexCommand('dropPrimary', 'primary', $index);
	}

	/**
	 * Specify a unique index for the table.
	 *
	 * @param string|array $columns
	 * @param string $name
	 * @param string|null $algorithm
	 * @return Command
	 */
	public function unique($columns, $name = null, $algorithm = null) {
		return $this->indexCommand('unique', $columns, $name, $algorithm);
	}

	/**
	 * Indicate that the given unique key should be dropped.
	 *
	 * @param string|array $index
	 * @return Command
	 * @throws
	 */
	public function dropUnique($index) {
		return $this->dropIndexCommand('dropUnique', 'unique', $index);
	}

	/**
	 * Specify an index for the table.
	 *
	 * @param string|array $columns
	 * @param string $name
	 * @param string|null $algorithm
	 * @return Command
	 */
	public function index($columns, $name = null, $algorithm = null) {
		return $this->indexCommand('index', $columns, $name, $algorithm);
	}

	/**
	 * Indicate that the given index should be dropped.
	 *
	 * @param string|array $index
	 * @return Command
	 * @throws
	 */
	public function dropIndex($index) {
		return $this->dropIndexCommand('dropIndex', 'index', $index);
	}

	/**
	 * Specify a foreign key for the table.
	 *
	 * @param string|array $columns
	 * @param string $name
	 * @return Command
	 */
	public function foreign($columns, $name = null) {
		return $this->indexCommand('foreign', $columns, $name);
	}

	/**
	 * Indicate that the given foreign key should be dropped.
	 *
	 * @param string|array $index
	 * @return Command
	 * @throws
	 */
	public function dropForeign($index) {
		return $this->dropIndexCommand('dropForeign', 'foreign', $index);
	}

	/**
	 * Add a new column to the blueprint.
	 *
	 * @param string $type
	 * @param string $name
	 * @param array $parameters
	 * @return Column
	 */
	public function addColumn($type, $name, array $parameters = array()) {
		return $this->columns[] = new Column($type, $name, $parameters, $this);
	}

	/**
	 * Remove a column from the schema blueprint.
	 *
	 * @param string $name
	 * @return $this
	 * @throws
	 */
	public function removeColumn($name) {
		if ($this->isCreating()) {
			throw new MigrationException('Cannot remove a column while in table creation mode.');
		}

		$this->columns = array_values(array_filter($this->columns, function ($c) use ($name) {
			return $c['attributes']['name'] != $name;
		}));

		return $this;
	}

	/**
	 * Add a new index command to the blueprint.
	 *
	 * @param string $type
	 * @param string|array $columns
	 * @param string $index
	 * @param string|null $algorithm
	 * @return Command
	 */
	protected function indexCommand($type, $columns, $index = null, $algorithm = null) {
		$columns = (array)$columns;
		$index = $index ?: $this->createIndexName($type, $columns);

		return $this->addCommand($type, compact('index', 'columns', 'algorithm'));
	}

	/**
	 * Create a new drop index command on the blueprint.
	 *
	 * @param string $command
	 * @param string $type
	 * @param string|array $index
	 * @return Command
	 * @throws
	 */
	protected function dropIndexCommand($command, $type, $index) {
		if ($this->isCreating()) {
			throw new MigrationException('Cannot drop an index while in table creation mode.');
		}

		$columns = array();

		if (is_array($index)) {
			$index = $this->createIndexName($type, $columns = $index);
		}

		return $this->indexCommand($command, $columns, $index);
	}

	/**
	 * Create a default index name for the table.
	 *
	 * @param string $type
	 * @param array $columns
	 * @return string
	 */
	protected function createIndexName($type, array $columns) {
		$index = strtolower(implode('_', $columns) . '_' . $type);
		return str_replace(array('-', '.'), '_', $index);
	}
	/**
	 * Add a new command to the blueprint.
	 *
	 * @param string $name
	 * @param array $parameters
	 * @return Command
	 */
	protected function addCommand($name, array $parameters = array()) {
		return $this->commands[] = new Command($name, $parameters, $this);
	}

	/**
	 * Get the table the blueprint describes.
	 *
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Returns true if the table is being created rather than modified.
	 *
	 * @return bool
	 */
	public function isCreating() {
		return $this->create;
	}

	/**
	 * Returns true if the table contains a primary key index.
	 *
	 * @return bool
	 */
	private function hasPrimaryKey() {
		foreach ($this->commands as $command) {
			if ($command->name == 'primary') {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the auto-incrementing column in the table.
	 *
	 * @return Column|null
	 */
	private function getIncrementingColumn() {
		foreach ($this->columns as $column) {
			if ($column->autoIncrementing || array_get($column->parameters, 'autoIncrement', false)) {
				return $column;
			}
		}

		return null;
	}

	/**
	 * Get the columns on the blueprint.
	 *
	 * @return Column[]
	 */
	public function getColumns() {
		return $this->columns;
	}

	/**
	 * Get the commands on the blueprint.
	 *
	 * @return Command[]
	 */
	public function getCommands() {
		return $this->commands;
	}

	/**
	 * Gets the SQL query for the table operation.
	 *
	 * @return string
	 */
	public function __toString() {
		if ($this->isCreating()) {
			if (!$this->hasPrimaryKey()) {
				if (!is_null($incrementing = $this->getIncrementingColumn())) {
					$this->primary($incrementing->name);
				}
			}

			return $this->compileCreateTable() . ';';
		}
		else {
			return $this->compileAlterTable() . ';';
		}
	}

	/**
	 * Compiles the blueprint as a CREATE TABLE operation.
	 *
	 * @return string
	 */
	private function compileCreateTable() {
		$query = array('CREATE TABLE', Grammar::compileName($this->schema->prefix($this->table)));

		// Build columns
		$columns = array();
		foreach ($this->columns as $column) {
			$columns[] = (string)$column;
		}

		// Add indexes
		foreach ($this->commands as $command) {
			$columns[] = (string)$command;
		}

		// Add columns and indexes to the table
		$query[] = '(' . implode(', ', $columns) . ')';

		return str_join($query);
	}

	/**
	 * Compiles the blueprint as an ALTER TABLE operation.
	 *
	 * @return string
	 */
	private function compileAlterTable() {
		$query = array('ALTER TABLE', Grammar::compileName($this->schema->prefix($this->table)));
		$specifications = array();

		// Add commands
		foreach ($this->commands as $command) {
			$specifications[] = (string)$command;
		}

		// Add columns
		foreach ($this->columns as $column) {
			$specifications[] = (string)$column;
		}

		// Delimit the specifications by commas
		$query[] = implode(', ', $specifications);

		return str_join($query);
	}

}
