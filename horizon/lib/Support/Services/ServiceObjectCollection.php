<?php

namespace Horizon\Support\Services;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * A collection of service objects.
 */
class ServiceObjectCollection implements ArrayAccess, Countable, IteratorAggregate {

	/**
	 * The items contained in the collection.
	 *
	 * @var object[]
	 */
	protected $items = array();

	/**
	 * An internal index tracker for use with next().
	 *
	 * @var int
	 */
	protected $index = 0;

	/**
	 * Constructs a new ServiceObjectCollection instance.
	 *
	 * @param object[] $objects
	 */
	public function __construct(array $objects = array()) {
		$this->items = $objects;
	}

	/**
	 * Retrieves all objects in the collection.
	 *
	 * @return object[]
	 */
	public function all() {
		return $this->items;
	}

	/**
	 * Retrieves the next object in the collection, or null if none remain.
	 *
	 * @return object|null
	 */
	public function next() {
		if (array_key_exists($this->index, $this->items)) {
			return $this->items[$this->index++];
		}

		return null;
	}

	/**
	 * Retrieves the first object in the collection, or null if it is empty.
	 *
	 * @return object|null
	 */
	public function first() {
		if (!array_key_exists(0, $this->items)) return null;
		return $this->items[0];
	}

	/**
	 * Retrieves an external iterator.
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->items);
	}

	/**
	 * Determines if an item exists at an offset.
	 *
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->items);
	}

	/**
	 * Retrieves the item at a given offset.
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->items[$offset];
	}

	/**
	 * Sets the item at a given offset.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->items[] = $value;
		}
		else {
			$this->items[$offset] = $value;
		}
	}

	/**
	 * Unsets the item at a given offset.
	 *
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->items[$offset]);
	}

	/**
	 * Counts the number of items in the collection.
	 *
	 * @return int
	 */
	public function count() {
		return count($this->items);
	}

}
