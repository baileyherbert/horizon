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
	 *
	 * @param object|null $mapping A database row as an object, to map this model instance to.
	 */
	public function __construct($mapping = null) {
		if (is_object($mapping)) {
			foreach ($mapping as $column => $value) {
				$this->storage[$column] = $value;
			}
		}
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
