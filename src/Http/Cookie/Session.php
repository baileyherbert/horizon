<?php

namespace Horizon\Http\Cookie;

use Horizon\Exception\HorizonException;
use Horizon\Http\Cookie\Drivers\DriverInterface;
use Horizon\Support\Profiler;

class Session {

	/**
	 * @var DriverInterface
	 */
	private $driver;

	public function __construct($desiredDriver = null) {
		$this->load($desiredDriver);
	}

	private function load($desiredDriver = null) {
		static $DRIVERS = array(
			'cookie' => 'Horizon\Http\Cookie\Drivers\CookieDriver',
			'database' => 'Horizon\Http\Cookie\Drivers\DatabaseDriver',
			'array' => 'Horizon\Http\Cookie\Drivers\MemoryDriver'
		);

		// Load from configuration if no driver was specified at runtime
		if (is_null($desiredDriver)) {
			$desiredDriver = config('session.driver', 'cookie');
		}

		// Throw an exception if the session driver is not found
		if (!array_key_exists($desiredDriver, $DRIVERS)) {
			throw new HorizonException(0x0004, 'Driver not found (session/' . (string)$desiredDriver . ')');
		}

		// Get the driver class
		$driver = $DRIVERS[$desiredDriver];
		Profiler::record('Boot session driver', $driver);

		// Throw an exception if the session driver is not found
		if (!class_exists($driver)) {
			throw new HorizonException(0x0004, 'Driver autoload failed (' . $driver . ')');
		}

		$this->driver = new $driver($this);
	}

	/**
	 * Determines if the session has the specified key AND it is not null.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		return $this->driver->has($key);
	}

	/**
	 * Determines if the session has the specified key. Returns true even if the value is null.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function exists($key) {
		return $this->driver->exists($key);
	}

	/**
	 * Alias for `set()`.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function put($key, $value) {
		return $this->driver->put($key, $value);
	}

	/**
	 * Stores data in the session under the specified key, overwriting any existing values.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function set($key, $value) {
		return $this->driver->put($key, $value);
	}

	/**
	 * Gets the value of the specified key in the session.  If the key does not exist, returns the specified default
	 * value (or null).
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return $this->driver->get($key, $default);
	}

	/**
	 * Gets the value of the specified key and then removes it from the session. If the key does not exist, returns
	 * the specified default value (or null).
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function pull($key, $default = null) {
		return $this->driver->pull($key, $default);
	}

	/**
	 * Forgets the specified key, effectively removing it from the session.
	 *
	 * @param string $key
	 * @return void
	 */
	public function forget($key) {
		return $this->driver->forget($key);
	}

	/**
	 * Clears all data from the session.
	 *
	 * @return void
	 */
	public function clear() {
		return $this->driver->clear();
	}

	/**
	 * Flashes data to the session which will only persist until the next session activation (typically the next
	 * pageload).
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function flash($key, $value) {
		return $this->driver->flash($key, $value);
	}

	/**
	 * Reflashes the current flashed data, effectively persisting it until the next pageload. Note that if any keys in
	 * the reflashed data have been written to the current flash, they will be overwritten with the old data.
	 */
	public function reflash() {
		return $this->driver->reflash();
	}

	/**
	 * Reflashes the specified keys only. Does not error if the specified keys do not exist.
	 * @see reflash()
	 *
	 * @param string[] $keys
	 */
	public function keep(array $keys) {
		return $this->driver->keep($keys);
	}

	/**
	 * Gets an array of all payload keys.
	 *
	 * @return array
	 */
	public function all() {
		return $this->driver->all();
	}

	/**
	 * Gets the current CSRF token.
	 *
	 * @return string
	 */
	public function csrf() {
		return $this->driver->csrf();
	}

	/**
	 * Releases the current CSRF token and generates a new one.
	 *
	 * @return string
	 */
	public function renew() {
		return $this->driver->renew();
	}

	/**
	 * Returns an array containing the following about the specified key.

	 *   - `type` (string)
	 *   - `size` (int)
	 *
	 * @param string $key
	 * @return array
	 */
	public function stat($key) {
		return $this->driver->stat($key);
	}

}
