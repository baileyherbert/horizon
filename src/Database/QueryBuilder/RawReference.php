<?php

namespace Horizon\Database\QueryBuilder;

class RawReference {

	/**
	 * The raw value to use.
	 *
	 * @var string
	 */
	public $value;

	public function __construct($value) {
		$this->value = $value;
	}

	public function __toString() {
		return $this->value;
	}

}
