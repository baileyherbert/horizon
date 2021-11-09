<?php

namespace Horizon\Events;

class EventEmitter {

	/**
	 * @var EventCallback[][]
	 */
	private $hooks = array();

	/**
	 * Emits an event on the specified channel with the provided arguments.
	 *
	 * @param string $channel The channel to emit on.
	 * @param mixed [$args...] Optional arguments to send to the callbacks.
	 */
	protected function emit() {
		// Get all arguments
		$args = func_get_args();

		// Check argument count
		if (count($args) == 0) return;

		// Get channel
		$channel = array_shift($args);

		// Execute bound callbacks
		$this->call($channel, $args);
	}

	/**
	 * Executes the callbacks for the specified channel with the provided arguments array.
	 *
	 * @param string $channel
	 * @param array $args
	 */
	private function call($channel, array &$args = array()) {
		// Skip if no callbacks exist
		if (!isset($this->hooks[$channel])) return;

		// Execute each callback with the provided arguments
		foreach ($this->hooks[$channel] as $callback) {
			$callback->execute($args);
		}

		// Remove inactive callbacks
		$this->clean($channel);
	}

	/**
	 * Cleans the specified channel of inactive callbacks.
	 */
	private function clean($channel) {
		// Skip if no callbacks exist
		if (!isset($this->hooks[$channel])) return;

		// Reverse iterate over callbacks
		for ($i = (count($this->hooks[$channel]) - 1); $i >= 0; $i--) {
			// Get the callback object
			$callback = $this->hooks[$channel][$i];

			// Delete it from the array if inactive
			if (!$callback || !$callback->getActive()) {
				unset($this->hooks[$channel][$i]);
			}
		}
	}

	/**
	 * Creates an array for the channel if it doesn't exist.
	 */
	private function prepare($channel) {
		if (!isset($this->hooks[$channel])) {
			$this->hooks[$channel] = array();
		}
	}

	/**
	 * Binds the callback function to be executed each time the channel is invoked.
	 *
	 * @param string $channel Event channel.
	 * @param callable $callback Callback function.
	 */
	public function on($channel, callable $callback) {
		// Create an array for the channel if needed
		$this->prepare($channel);

		// Add the callback to the channel
		$this->hooks[$channel][] = new EventCallback($callback, EVENT_EVERY);
	}

	/**
	 * Binds the callback function to be executed the next time the channel is invoked, after which the
	 * binding will be destroyed and the callback will no longer be invoked.
	 *
	 * @param string $channel Event channel.
	 * @param callable $callback Callback function.
	 */
	public function once($channel, callable $callback) {
		// Create an array for the channel if needed
		$this->prepare($channel);

		// Add the callback to the channel
		$this->hooks[$channel][] = new EventCallback($callback, EVENT_ONCE);
	}

	/**
	 * Removes the callback from the specified event channel, if it exists.
	 *
	 * @param string $channel Event channel.
	 * @param callable $callback Callback function.
	 */
	public function remove($channel, callable $callback) {
		$index = -1;

		// Skip if no callbacks exist
		if (!isset($this->hooks[$channel])) return;

		// Find the index of the callable
		foreach ($this->hooks[$channel] as $i => $e) {
			if ($e->equals($callback)) {
				$index = $i;
			}
		}

		// Remove the index if found
		if ($index >= 0) {
			unset($this->hooks[$channel][$index]);
		}
	}

}
