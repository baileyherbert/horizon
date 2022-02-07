<?php

namespace Horizon\Database\Migration\Schema;

class ForeignCommand extends Command {

	protected $columnName = null;
	protected $tableName = null;

	protected $onUpdateConstraint = null;
	protected $onDeleteConstraint = null;

	/**
	 * Sets the foreign key as a reference to the specified column and table.
	 *
	 * @param string $tableName
	 * @param string $columnName
	 * @return $this
	 */
	public function references($tableName, $columnName) {
		$this->tableName = $tableName;
		$this->columnName = $columnName;
		return $this;
	}

	/**
	 * @param string $action The action to perform when a referenced row is deleted.
	 *
	 *   - `cascade` – The foreign key will be deleted along with the row.
	 *   - `restrict` – The deletion will be rejected while the foreign key is active.
	 *   - `null` – The foreign key value will be set to null.
	 *
	 * @return $this
	 */
	public function onUpdate($action) {
		$this->onUpdateConstraint = $action;
		return $this;
	}

	/**
	 * @param string $action The action to perform when a referenced row is deleted.
	 *
	 *   - `cascade` – The foreign key will be deleted along with the row.
	 *   - `restrict` – The deletion will be rejected while the foreign key is active.
	 *   - `null` – The foreign key value will be set to null.
	 *
	 * @return $this
	 */
	public function onDelete($action) {
		$this->onDeleteConstraint = $action;
		return $this;
	}

	/**
	 * Compiles the command as an index.
	 *
	 * @return string
	 */
	protected function compileIndex() {
		$indexName = array_get($this->parameters, 'index');
		$columns = array_get($this->parameters, 'columns');

		$result = str_join(
			($this->table->isCreating()) ? '' : 'ADD',
			'CONSTRAINT', Grammar::compileName($indexName),
			Grammar::getKey($this->name),
			Grammar::compileName($indexName),
			Grammar::compileColumnList($columns),
			'REFERENCES',
			Grammar::compileName($this->tableName),
			Grammar::compileColumnList((array) $this->columnName)
		);

		if ($this->onDeleteConstraint) {
			$result .= ' ' . str_join(
				'ON DELETE',
				strtoupper($this->onDeleteConstraint)
			);
		}

		if ($this->onUpdateConstraint) {
			$result .= ' ' . str_join(
				'ON UPDATE',
				strtoupper($this->onUpdateConstraint)
			);
		}

		return $result;
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

}
