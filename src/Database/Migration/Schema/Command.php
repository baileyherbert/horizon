<?php

namespace Horizon\Database\Migration\Schema;

use Horizon\Database\Migration\Blueprint;

/**
 * Represents a schema blueprint statement.
 */
class Command {

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var Blueprint
	 */
	protected $table;

	/**
	 * Creates a command.
	 *
	 * @param string $name
	 * @param array $parameters
	 * @param Blueprint $table
	 */
	public function __construct($name, array $parameters, Blueprint $table) {
		$this->name = $name;
		$this->parameters = $parameters;
		$this->table = $table;
	}

	/**
	 * Returns true if the command is for a table index.
	 *
	 * @return bool
	 */
	protected function isIndex() {
		return ($this->name == 'primary' || $this->name == 'foreign' || $this->name == 'index' || $this->name == 'unique');
	}

	/**
	 * Returns true if the command is for dropping an index.
	 *
	 * @return bool
	 */
	protected function isDropIndex() {
		return ($this->name == 'dropPrimary' || $this->name == 'dropForeign' || $this->name == 'dropIndex' || $this->name == 'dropUnique');
	}

	/**
	 * Converts the command into SQL.
	 *
	 * @return string
	 */
	public function __toString() {
		if ($this->isIndex()) return $this->compileIndex();
		if ($this->isDropIndex()) return $this->compileDropIndex();
		if ($this->name === 'dropColumn') return $this->compileDropColumn();

		return 'Unknown command: ' . $this->name . ' -> ' . json_encode($this->parameters);
	}

	/**
	 * Compiles the command as an index.
	 *
	 * @return string
	 */
	protected function compileIndex() {
		$indexName = array_get($this->parameters, 'index');
		$columns = array_get($this->parameters, 'columns');

		return str_join(
			($this->table->isCreating()) ? '' : 'ADD',
			Grammar::getKey($this->name),
			Grammar::compileName($indexName),
			Grammar::compileColumnList($columns)
		);
	}

	/**
	 * Compiles the command as an index.
	 *
	 * @return string
	 */
	protected function compileDropIndex() {
		$indexName = array_get($this->parameters, 'index');

		return str_join(
			'DROP',
			Grammar::getKey($this->name),
			$this->name !== 'dropPrimary' ? Grammar::compileName($indexName) : ''
		);
	}

	/**
	 * Compiles the command as an index.
	 *
	 * @return string
	 */
	protected function compileDropColumn() {
		$columns = array_get($this->parameters, 'columns', array());
		$statements = array();

		foreach ($columns as $column) {
			$statements[] = str_join('DROP', Grammar::compileName($column));
		}

		return implode(', ', $statements);
	}

}
