<?php

namespace Horizon\Database\QueryBuilder\Commands;

use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\StringBuilder;
use Horizon\Support\Str;
use Horizon\Support\Arr;
use Horizon\Database\Exception\QueryBuilderException;

class Drop implements CommandInterface
{

	/**
	 * @var QueryBuilder
	 */
	protected $builder;

	/**
	 * @var string|null
	 */
	protected $table;

	/**
	 * @var string|null
	 */
	protected $database;

	/**
	 * @var string|null
	 */
	protected $ifExists = false;

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
	 * Compiles the query into a string.
	 *
	 * @return string
	 */
	public function compile()
	{
		if (!is_null($this->database)) {
			return Str::join(
				'DROP DATABASE',
				($this->ifExists ? 'IF EXISTS' : ''),
				StringBuilder::formatTableName($this->database)
			) . ';';
		}

		if (!is_null($this->table)) {
			return Str::join(
				'DROP TABLE',
				($this->ifExists ? 'IF EXISTS' : ''),
				StringBuilder::formatTableName($this->builder->getPrefix() . $this->table)
			) . ';';
		}

		return '';
	}

	/**
	 * Drops the specified table.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function table($name)
	{
		$this->table = $name;
		return $this;
	}

	/**
	 * Drops the specified database.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function database($name)
	{
		$this->database = $name;
		return $this;
	}

	/**
	 * Only drops the target if it exists.
	 *
	 * @param bool $bool
	 * @return $this
	 */
	public function ifExists($bool = true)
	{
		$this->ifExists = $bool;
		return $this;
	}

	public function getParameters()
	{
		return array();
	}

}
