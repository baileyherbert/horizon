<?php

namespace Horizon\Database;

use Horizon\Events\EventEmitter;

/**
 * Represents a row of data in the database.
 */
class Model extends EventEmitter implements \JsonSerializable {

	use ORM\Traits\Mapping;
	use ORM\Traits\QueryBuilding;
	use ORM\Traits\Serializable;

	/**
	 * Constructs a new model object.
	 */
	public function __construct() {
		$mapping = null;

		if (func_num_args() === 1) {
			$mapping = func_get_arg(0);

			if (is_object($mapping)) {
				foreach ($mapping as $column => $value) {
					$this->writeCommittedField($column, $value);
				}
			}
		}

		$this->init();

		if (is_object($mapping)) {
			$this->initWithData();
		}
	}

	/**
	 * Override this method to initialize the model. This is called automatically when the model is instantiated, even
	 * if the model has no data.
	 *
	 * @return void
	 */
	protected function init() {

	}

	/**
	 * Override this method to initialize the model when data is available. This will be invoked from the constructor
	 * if the model has data, or it will be invoked when the model is created.
	 *
	 * @return void
	 */
	protected function initWithData() {

	}

	/**
	 * Converts the model instance to a JSON-serialized string,
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->toJson();
	}

	/**
	 * Returns the serialization array for the model.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->toArray();
	}

}
