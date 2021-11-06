<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Support\Str;
use Horizon\Support\Arr;
use Horizon\Database\Exception\QueryBuilderException;

/**
 * @method ShowHelper tables() Sets the query to show tables.
 * @method ShowHelper tableStatus() Sets the query to show tables.
 * @method ShowHelper columns(string $table) Sets the query to show tables (optionally against a pattern).
 * @method ShowHelper databases() Sets the query to show databases.
 *
 * @method ShowHelper createTable(string $table) Sets the query to show table creation query.
 *
 * @method string compile() Gets the query as a prepared string.
 */
class Show implements CommandInterface
{

	/**
	 * @var QueryBuilder
	 */
	protected $builder;

	/**
	 * @var string
	 */
	protected $query = '';

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
	 * Gets the query as a string.
	 *
	 * @return string
	 */
	public function compile()
	{
		return "{$this->query};";
	}

	/**
	 * SHOW DATABASES
	 *
	 * @return $this
	 */
	public function databases()
	{
		$this->query = 'SHOW DATABASES';
		return $this;
	}

	/**
	 * SHOW TABLES
	 *
	 * @return $this
	 */
	public function tables()
	{
		$this->query = 'SHOW TABLES';
		return $this;
	}

	/**
	 * SHOW TABLE STATUS
	 *
	 * @return $this
	 */
	public function tableStatus()
	{
		$this->query = 'SHOW TABLE STATUS';
		return $this;
	}

	/**
	 * SHOW TABLES FROM {tbl}
	 *
	 * @return $this
	 */
	public function columns($table)
	{
		$this->query = 'SHOW COLUMNS FROM ' . StringBuilder::formatTableName($this->builder->getPrefix() . $table);
		return $this;
	}

	/**
	 * SHOW CREATE TABLE {tbl}
	 *
	 * @return $this
	 */
	public function createTable($table)
	{
		$this->query = 'SHOW CREATE TABLE ' . StringBuilder::formatTableName($this->builder->getPrefix() . $table);
		return $this;
	}

	public function getParameters()
	{
		return array();
	}

}
