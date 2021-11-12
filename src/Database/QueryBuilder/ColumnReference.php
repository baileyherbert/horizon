<?php

namespace Horizon\Database\QueryBuilder;

class ColumnReference {

	/**
	 * The name of the column.
	 *
	 * @var string
	 */
	public $name;

	public function __construct($name) {
		$this->name = $name;
	}

}
