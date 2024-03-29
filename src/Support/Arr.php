<?php

// To stay as close to Laravel as possible, this file is mostly adopted from illuminate/support.
// Referenced from version 5.4: https://github.com/laravel/framework/blob/5.4/src/Illuminate/Support/Arr.php

namespace Horizon\Support;

use ArrayAccess;
use InvalidArgumentException;

class Arr {

	/**
	 * Determine whether the given value is array accessible.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public static function accessible($value) {
		return is_array($value) || $value instanceof ArrayAccess;
	}

	/**
	 * Add an element to an array using "dot" notation if it doesn't exist.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	public static function add($array, $key, $value) {
		if (is_null(static::get($array, $key))) {
			static::set($array, $key, $value);
		}

		return $array;
	}

	/**
	 * Collapse an array of arrays into a single array.
	 *
	 * @param array $array
	 * @return array
	 */
	public static function collapse($array) {
		$results = array();

		foreach ($array as $values) {
			if (!is_array($values)) continue;
			$results = array_merge($results, $values);
		}

		return $results;
	}

	/**
	 * Divide an array into two arrays. One with keys and the other with values.
	 *
	 * @param array $array
	 * @return array
	 */
	public static function divide($array) {
		return array(array_keys($array), array_values($array));
	}

	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @param array $array
	 * @param string $prepend
	 * @return array
	 */
	public static function dot($array, $prepend = '') {
		$results = array();

		foreach ($array as $key => $value) {
			if (is_array($value) && !empty($value)) {
				$results = array_merge($results, static::dot($value, $prepend . $key . '.'));
			}
			else {
				$results[$prepend . $key] = $value;
			}
		}

		return $results;
	}

	/**
	 * Get all of the given array except for a specified array of items.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return array
	 */
	public static function except($array, $keys) {
		static::forget($array, $keys);
		return $array;
	}

	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @param ArrayAccess|array $array
	 * @param string|int $key
	 * @return bool
	 */
	public static function exists($array, $key) {
		if ($array instanceof ArrayAccess) {
			return $array->offsetExists($key);
		}

		return array_key_exists($key, $array);
	}

	/**
	 * Return the first element in an array passing a given truth test. If no truth test is provided, returns the
	 * very first element in the array.
	 *
	 * @param array $array
	 * @param callable|null $callback
	 * @param mixed $default
	 * @return mixed
	 */
	public static function first($array, $callback = null, $default = null) {
		if (is_null($callback)) {
			if (empty($array)) {
				return $default;
			}

			foreach ($array as $item) {
				return $item;
			}
		}

		foreach ($array as $key => $value) {
			if (call_user_func($callback, $value, $key)) {
				return $value;
			}
		}

		return $default;
	}

	/**
	 * Return the last element in an array passing a given truth test. If no truth test is provided, returns the very
	 * last element in the array.
	 *
	 * @param array $array
	 * @param callable|null $callback
	 * @param mixed $default
	 * @return mixed
	 */
	public static function last($array, $callback = null, $default = null) {
		if (is_null($callback)) {
			return empty($array) ? $default : end($array);
		}

		return static::first(array_reverse($array, true), $callback, $default);
	}

	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param array $array
	 * @param int $depth
	 * @return array
	 */
	public static function flatten($array, $depth = INF) {
		return array_reduce($array, function ($result, $item) use ($depth) {
			if (!is_array($item)) {
				return array_merge($result, [$item]);
			}
			else if ($depth === 1) {
				return array_merge($result, array_values($item));
			}
			else {
				return array_merge($result, static::flatten($item, $depth - 1));
			}
		}, array());
	}

	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return void
	 */
	public static function forget(&$array, $keys) {
		$original = &$array;
		$keys = (array)$keys;

		if (count($keys) === 0) {
			return;
		}

		foreach ($keys as $key) {
			if (static::exists($array, $key)) {
				unset($array[$key]);
				continue;
			}

			$parts = explode('.', $key);
			$array = &$original;

			while (count($parts) > 1) {
				$part = array_shift($parts);

				if (isset($array[$part]) && is_array($array[$part])) {
					$array = &$array[$part];
				}
				else {
					continue 2;
				}
			}

			unset($array[array_shift($parts)]);
		}
	}

	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param ArrayAccess|array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($array, $key, $default = null) {
		if (!static::accessible($array)) return $default;
		if (is_null($key)) return $array;
		if (static::exists($array, $key)) return $array[$key];

		foreach (explode('.', $key) as $segment) {
			if (static::accessible($array) && static::exists($array, $segment)) {
				$array = $array[$segment];
			}
			else {
				return $default;
			}
		}

		return $array;
	}

	/**
	 * Check if an item or items exist in an array using "dot" notation.
	 *
	 * @param ArrayAccess|array $array
	 * @param string|array $keys
	 * @return bool
	 */
	public static function has($array, $keys) {
		if (is_null($keys)) return false;
		$keys = (array)$keys;

		if (!$array) return false;
		if ($keys === []) return false;

		foreach ($keys as $key) {
			$subKeyArray = $array;

			if (static::exists($array, $key)) continue;

			foreach (explode('.', $key) as $segment) {
				if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
					$subKeyArray = $subKeyArray[$segment];
				}
				else {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Determines if an array is associative. An array is "associative" if it doesn't have sequential numerical keys
	 * beginning with zero.
	 *
	 * @param array $array
	 * @return bool
	 */
	public static function isAssoc($array) {
		// Yo Taylor, nice strategy with this one! ^^

		$keys = array_keys($array);
		return array_keys($keys) !== $keys;
	}

	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return array
	 */
	public static function only($array, $keys) {
		return array_intersect_key($array, array_flip((array) $keys));
	}

	/**
	 * Pluck an array of values from an array.
	 *
	 * @param array $array
	 * @param string|array $value
	 * @param string|array|null $key
	 * @return array
	 */
	public static function pluck($array, $value, $key = null) {
		$results = array();
		list($value, $key) = static::explodePluckParameters($value, $key);

		foreach ($array as $item) {
			$itemValue = static::get($item, $value);

			if (is_null($key)) {
				$results[] = $itemValue;
			}
			else {
				$itemKey = static::get($item, $key);

				if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
					$itemKey = (string) $itemKey;
				}

				$results[$itemKey] = $itemValue;
			}
		}

		return $results;
	}

	/**
	 * Explode the "value" and "key" arguments passed to "pluck".
	 *
	 * @param string|array $value
	 * @param string|array|null $key
	 * @return array
	 */
	private static function explodePluckParameters($value, $key) {
		$value = is_string($value) ? explode('.', $value) : $value;
		$key = is_null($key) || is_array($key) ? $key : explode('.', $key);

		return array($value, $key);
	}

	/**
	 * Push an item onto the beginning of an array.
	 *
	 * @param array $array
	 * @param mixed $value
	 * @param mixed $key
	 * @return array
	 */
	public static function prepend($array, $value, $key = null) {
		if (is_null($key)) {
			array_unshift($array, $value);
		}
		else {
			$array = [$key => $value] + $array;
		}

		return $array;
	}

	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function pull(&$array, $key, $default = null) {
		$value = static::get($array, $key, $default);
		static::forget($array, $key);

		return $value;
	}

	/**
	 * Get one or a specified number of random values from an array.
	 *
	 * @param array $array
	 * @param int|null $number
	 * @return mixed
	 *
	 * @throws InvalidArgumentException
	 */
	public static function random($array, $number = null) {
		$requested = is_null($number) ? 1 : $number;
		$count = count($array);

		if ($requested > $count) {
			throw new InvalidArgumentException(
				"You requested {$requested} items, but there are only {$count} items available."
			);
		}

		if (is_null($number)) {
			return $array[array_rand($array)];
		}

		if ((int)$number === 0) {
			return array();
		}

		$keys = array_rand($array, $number);
		$results = array();

		foreach ((array) $keys as $key) {
			$results[] = $array[$key];
		}

		return $results;
	}

	/**
	 * Set an array item to a given value using "dot" notation. If no key is given to the method, the entire array will
	 * be replaced.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	public static function set(&$array, $key, $value) {
		if (is_null($key)) {
			return $array = $value;
		}

		$keys = explode('.', $key);

		while (count($keys) > 1) {
			$key = array_shift($keys);

			if (!isset($array[$key]) || !is_array($array[$key])) {
				$array[$key] = [];
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;
		return $array;
	}

	/**
	 * Shuffle the given array and return the result.
	 *
	 * @param array $array
	 * @return array
	 */
	public static function shuffle($array) {
		shuffle($array);
		return $array;
	}

	/**
	 * Sort the array using the given callback or "dot" notation.
	 *
	 * @param array $array
	 * @param callable|string $callback
	 * @param int $flags The sorting algorithm to use
	 * @return array
	 */
	public static function sort($array, $callback, $flags = SORT_REGULAR) {
		$results = array();

		foreach ($array as $key => $value) {
			$results[$key] = $callback($value, $key);
		}

		asort($results, $flags);

		foreach (array_keys($results) as $key) {
			$results[$key] = $array[$key];
		}

		return $results;
	}

	/**
	 * Recursively sort an array by keys and values.
	 *
	 * @param array $array
	 * @return array
	 */
	public static function sortRecursive($array) {
		foreach ($array as &$value) {
			if (is_array($value)) {
				$value = static::sortRecursive($value);
			}
		}

		if (static::isAssoc($array)) {
			ksort($array);
		}
		else {
			sort($array);
		}

		return $array;
	}

	/**
	 * Filter the array using the given callback.
	 *
	 * @param array $array
	 * @param callable $callback
	 * @return array
	 */
	public static function where($array, $callback) {
		return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
	}

	/**
	 * If the given value is not an array, wrap it in one.
	 *
	 * @param mixed $value
	 * @return array
	 */
	public static function wrap($value) {
		return !is_array($value) ? array($value) : $value;
	}

	/**
	 * Runs a callable for each item in an array. You can return values from the callable to create a new array with
	 * those values for each key.
	 *
	 * @param $array
	 * @param $callable
	 * @return array
	 */
	public static function each($array, $callable) {
		$revised = array();

		foreach ($array as $key => $value) {
			$revised[$key] = $callable($value, $key);
		}

		return $revised;
	}

}
