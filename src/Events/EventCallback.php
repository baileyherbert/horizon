<?php

namespace Horizon\Events;

define('EVENT_ONCE', 0x01);
define('EVENT_EVERY', 0x02);

class EventCallback {

	/**
	 * @var callable The callback function.
	 */
	private $callable;

	/**
	 * @var int The type of event.
	 */
	private $type;

	/**
	 * @var bool Whether this callback is active or not.
	 */
	private $active = true;

	/**
	 * Constructor
	 */
	public function __construct(callable $callable, $type = EVENT_ONCE) {
		$this->callable = $callable;
		$this->type = $type;
	}

	/**
	 * Returns the type of callback.
	 *
	 * @return int One of EVENT_ONCE or EVENT_EVERY.
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Determines if this callback is active and can be executed.
	 *
	 * @return bool True if this callback can currently be executed.
	 */
	public function getActive() {
		return $this->active;
	}

	/**
	 * Runs the callback.
	 */
	public function execute(array &$arguments = array()) {
		// Skip if not active
		if (!$this->getActive()) return null;

		// Get return value
		$userFuncReturned = call_user_func_array($this->callable, $arguments);

		// Deactivate if one-time
		if ($this->getType() === EVENT_ONCE) {
			$this->destroy();
		}

		// Return the return value
		return $userFuncReturned;
	}

	/**
	 * @return bool Whether the provided callable is equal to this callable.
	 */
	public function equals(callable $callable) {
		return ($callable == $this->callable);
	}

	/**
	 * Destroys the callable and deactivates the callback.
	 */
	public function destroy() {
		$this->active = false;
		$this->callable = null;
	}

}
