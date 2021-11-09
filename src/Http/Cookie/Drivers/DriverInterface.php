<?php

namespace Horizon\Http\Cookie\Drivers;

use Horizon\Http\Cookie\Session;

interface DriverInterface {

	function __construct(Session $session);

	/**
	 * Determines if the session has the specified key AND it is not null.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Determines if the session has the specified key. Returns true even if the value is null.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function exists($key);

	/**
	 * Stores data in the session under the specified key, overwriting any existing values.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function put($key, $value);

	/**
	 * Gets the value of the specified key in the session.  If the key does not exist, returns the specified default
	 * value (or null).
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * Gets the value of the specified key and then removes it from the session. If the key does not exist, returns
	 * the specified default value (or null).
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function pull($key, $default = null);

	/**
	 * Forgets the specified key, effectively removing it from the session.
	 *
	 * @param string $key
	 * @return void
	 */
	public function forget($key);

	/**
	 * Clears all data from the session.
	 *
	 * @return void
	 */
	public function clear();

	/**
	 * Flashes data to the session which will only persist until the next session activation (typically the next
	 * pageload).
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function flash($key, $value);

	/**
	 * Reflashes the current flashed data, effectively persisting it until the next pageload. Note that if any keys in
	 * the reflashed data have been written to the current flash, they will be overwritten with the old data.
	 */
	public function reflash();

	/**
	 * Reflashes the specified keys only. Does not error if the specified keys do not exist.
	 * @see reflash()
	 *
	 * @param string[] $keys
	 */
	public function keep(array $keys);

	/**
	 * Gets an array of all payload keys.
	 *
	 * @return array
	 */
	public function all();

	/**
	 * Gets the current CSRF token.
	 *
	 * @return string
	 */
	public function csrf();

	/**
	 * Releases the current CSRF token and generates a new one.
	 *
	 * @return string
	 */
	public function renew();

}
