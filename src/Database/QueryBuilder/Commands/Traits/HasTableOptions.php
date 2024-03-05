<?php

namespace Horizon\Database\QueryBuilder\Commands\Traits;

use Horizon\Support\Str;

trait HasTableOptions {

	/**
	 * @var string[]
	 */
	protected $tableOptions = array();

	/**
	 * Compiles table options.
	 *
	 * @return string
	 */
	protected function compileOptions() {
		$compiled = array();

		foreach ($this->tableOptions as $option => $value) {
			$compiled[] = $option . ' = ' . $value;
		}

		return Str::join($compiled);
	}

	/**
	 * Returns table options.
	 *
	 * @return mixed
	 */
	protected function getOptions() {
		return $this->tableOptions;
	}

	/**
	 * Sets the engine.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function engine($name) {
		$this->tableOptions['ENGINE'] = $name;
		return $this;
	}

	/**
	 * Sets the character set.
	 *
	 * @param string $charset
	 * @return $this
	 */
	public function charset($charset) {
		$this->tableOptions['CHARACTER SET'] = $charset;
		return $this;
	}

	/**
	 * Sets the collation.
	 *
	 * @param string $collate
	 * @return $this
	 */
	public function collate($collate) {
		$this->tableOptions['COLLATE'] = $collate;
		return $this;
	}

	/**
	 * Sets an option manully.
	 *
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function opt($name, $value) {
		$this->tableOptions[strtoupper($name)] = $value;
		return $this;
	}

}
