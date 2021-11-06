<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Database\QueryBuilder\ColumnDefinition;
use Horizon\Support\Str;
use Horizon\Support\Arr;
use Horizon\Database\Exception\QueryBuilderException;

class Create implements CommandInterface
{

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
	 * @var ColumnDefinition[]
	 */
	protected $columns = array();

	/**
	 * @var array
	 */
	protected $keys = array();

	/**
	 * @var string[]
	 */
	protected $primaryKey = array();

	/**
	 * @var bool
	 */
	protected $ifNotExists = false;

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
	 * Compiles the statement into a string query.
	 *
	 * @return string
	 */
	public function compile()
	{
		return Str::join(
			'CREATE TABLE',
			$this->compileTableName(),
			($this->ifNotExists ? 'IF NOT EXISTS' : ''),
			$this->compileSchema(),
			$this->compileOptions()
		) . ';';
	}

	/**
	 * Compiles the table name.
	 *
	 * @return string
	 */
	protected function compileTableName()
	{
		$prefix = $this->builder->getPrefix();
		return StringBuilder::formatTableName($prefix . $this->table);
	}

	/**
	 * Compiles the table schema.
	 *
	 * @return string
	 */
	protected function compileSchema()
	{
		$compiled = array();
		$keys = $this->compileKeys();

		foreach ($this->columns as $column) {
			$compiled[] = $column->compile();
		}

		if ($keys) {
			$compiled[] = $keys;
		}

		return sprintf('(%s)', implode(', ', $compiled));
	}

	/**
	 * Compiles the table keys (automatically included in the schema).
	 *
	 * @return string
	 */
	public function compileKeys()
	{
		$compiled = array();

		if (!empty($this->primaryKey)) {
			$compiled[] = sprintf('PRIMARY KEY (%s)', $this->compileColumnList($this->primaryKey));
		}

		foreach ($this->keys as $key) {
			$keyColumns = $key['columns'];
			$keyType = $key['type'];

			if ($keyType == 'FOREIGN KEY') {
				$compiled[] = $this->compileForeignKey($key);
				continue;
			}

			$keyName = StringBuilder::formatColumnName($this->table . '_' . implode('_', $keyColumns));

			$compiled[] = sprintf('%s %s (%s)', $keyType, $keyName, $this->compileColumnList($keyColumns));
		}

		return implode(', ', $compiled);
	}

	/**
	 * Compiles a foreign key (automatically included in the schema).
	 *
	 * @param array $key
	 * @return string
	 */
	protected function compileForeignKey(array $key)
	{
		$compiled = array('FOREIGN KEY');

		// Generate the unique foreign key name
		$keyName = StringBuilder::formatColumnName('fk_' .
			$this->table .
			'_' . implode('_', $key['columns']) .
			'_' . $key['references']['table'] .
			'_' . implode('_', $key['references']['columns']));
		$compiled[] = $keyName;

		// Add the local column list
		$compiled[] = sprintf('(%s)', $this->compileColumnList($key['columns']));

		// Add the reference
		$compiled[] = 'REFERENCES';
		$compiled[] = StringBuilder::formatTableName($this->builder->getPrefix() . $key['references']['table']);
		$compiled[] = sprintf('(%s)', $this->compileColumnList($key['references']['columns']));

		// Add actions
		if (isset($key['onDelete'])) $compiled[] = 'ON DELETE ' . strtoupper($key['onDelete']);
		if (isset($key['onUpdate'])) $compiled[] = 'ON UPDATE ' . strtoupper($key['onUpdate']);

		return Str::join($compiled);
	}

	/**
	 * Compiles a list of columns into an SQL-ready, comma-delimited list.
	 *
	 * @param string[] $columns
	 * @return string
	 */
	protected function compileColumnList(array $columns)
	{
		$columnList = array();
		foreach ($columns as $colName) {
			$columnList[] = StringBuilder::formatColumnName($colName);
		}

		return implode(', ', $columnList);
	}

	/**
	 * Sets the name of the table to create.
	 *
	 * @param string $tableName
	 * @return $this
	 */
	public function table($tableName)
	{
		$this->table = $tableName;
		return $this;
	}

	/**
	 * Adds a column.
	 *
	 * @param ColumnDefinition $column
	 * @return $this
	 */
	public function column(ColumnDefinition $column)
	{
		$this->columns[] = $column;
	}

	/**
	 * Sets the columns.
	 *
	 * @param ColumnDefinition[] $columns
	 * @return $this
	 */
	public function columns(array $columns)
	{
		$this->columns = $columns;
	}

	/**
	 * Adds a PRIMARY KEY INDEX to one or more columns.
	 *
	 * @param string $column, ...
	 * @return $this
	 */
	public function primary()
	{
		$this->primaryKey = func_get_args();
		return $this;
	}

	/**
	 * Adds an INDEX to one or more columns.
	 *
	 * @param string $column, ...
	 * @return $this
	 */
	public function index()
	{
		$this->keys[] = array(
			'type' => 'INDEX',
			'columns' => func_get_args()
		);

		return $this;
	}

	/**
	 * Adds a UNIQUE INDEX to one or more columns.
	 *
	 * @param string $column, ...
	 * @return $this
	 */
	public function unique()
	{
		$this->keys[] = array(
			'type' => 'UNIQUE',
			'columns' => func_get_args()
		);

		return $this;
	}

	/**
	 * Adds a FOREIGN KEY INDEX to one or more columns.
	 *
	 * @param string|string[] $column
	 * @param string $foreignTable
	 * @param string|string[] $foreignColumn
	 * @param string|null $onDelete
	 * @param string|null $onUpdate
	 * @return $this
	 */
	public function foreign($column, $foreignTable, $foreignColumn, $onDelete = null, $onUpdate = null)
	{
		// Ensure the columns are arrays
		if (!is_array($foreignColumn)) $foreignColumn = array($foreignColumn);
		if (!is_array($column)) $column = array($column);

		$this->keys[] = array(
			'type' => 'FOREIGN KEY',
			'columns' => $column,
			'references' => array(
				'table' => $foreignTable,
				'columns' => $foreignColumn,
			),
			'onDelete' => $onDelete,
			'onUpdate' => $onUpdate
		);

		return $this;
	}

	/**
	 * Creates the table only if it doesn't exist.
	 *
	 * @param bool $bool
	 * @return $this
	 */
	public function ifNotExists($bool = true)
	{
		$this->ifNotExists = $bool;
		return $this;
	}

	public function getParameters()
	{
		return array();
	}

}
