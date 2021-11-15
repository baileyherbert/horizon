<?php

use Horizon\Database\QueryBuilder\ColumnReference;
use Horizon\Support\Arr;
use Horizon\Support\Container\Container;
use Horizon\Support\Path;
use Horizon\Support\Str;
use Horizon\Foundation\Application;
use Horizon\Encryption\FastEncrypt;
use Horizon\Exception\ErrorMiddleware;
use Horizon\Exception\HorizonError;
use Horizon\Exception\HorizonException;
use Horizon\Foundation\Services\Environment;

if (!function_exists('camel_case')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Convert a value to camel case.
	 *
	 * @param string $value
	 * @return string
	 */
	function camel_case($value) {
		return Str::camel($value);
	}
}

if (!function_exists('str_join')) {
	/**
	 * Joins two or more strings together by spaces, ignoring blank or empty strings (or those only consisting of
	 * whitespace), and preventing duplicate spaces.
	 *
	 * @param string ...$parts
	 * @return string
	 */
	function str_join() {
		return forward_static_call_array(array('Horizon\Support\Str', 'join'), func_get_args());
	}
}

if (!function_exists('starts_with')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Determine if a given string starts with a given substring.
	 *
	 * @param string $haystack
	 * @param string|array $needles
	 * @param bool $ignoreCase
	 * @return bool
	 */
	function starts_with($haystack, $needles, $ignoreCase = false) {
		if ($ignoreCase) {
			return Str::startsWithIgnoreCase($haystack, $needles);
		}

		return Str::startsWith($haystack, $needles);
	}
}

if (!function_exists('ends_with')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Determine if a given string ends with a given substring.
	 *
	 * @param string $haystack
	 * @param string|array $needles
	 * @param bool $ignoreCase
	 * @return bool
	 */
	function ends_with($haystack, $needles, $ignoreCase = false) {
		if ($ignoreCase) {
			return Str::endsWithIgnoreCase($haystack, $needles);
		}

		return Str::endsWith($haystack, $needles);
	}
}

if (!function_exists('str_contains')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Determine if a given string contains a given substring.
	 *
	 * @param string $haystack
	 * @param string|array $needles
	 * @return bool
	 */
	function str_contains($haystack, $needles) {
		return Str::contains($haystack, $needles);
	}
}

if (!function_exists('str_finish')) {
	/**
	 * Cap a string with a single instance of a given value.
	 *
	 * @param string $value
	 * @param string $cap
	 * @return string
	 */
	function str_finish($value, $cap) {
		return Str::finish($value, $cap);
	}
}

if (!function_exists('str_is')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Determine if a given string matches a given pattern.
	 *
	 * @param string|array $pattern
	 * @param string $value
	 * @return bool
	 */
	function str_is($pattern, $value) {
		return Str::is($pattern, $value);
	}
}

if (!function_exists('str_length')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Return the length of the given string.
	 *
	 * @param string $value
	 * @return int
	 */
	function str_length($value) {
		return Str::length($value);
	}
}

if (!function_exists('str_limit')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Limit the number of characters in a string.
	 *
	 * @param string $value
	 * @param int $limit
	 * @param string $end
	 * @return string
	 */
	function str_limit($value, $limit = 100, $end = '...') {
		return Str::limit($value, $limit, $end);
	}
}

if (!function_exists('str_lower')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Convert the given string to lower-case.
	 *
	 * @param string $value
	 * @return string
	 */
	function str_lower($value) {
		return Str::lower($value);
	}
}

if (!function_exists('str_limit_words')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Limit the number of words in a string.
	 *
	 * @param string $value
	 * @param int $words
	 * @param string $end
	 * @return string
	 */
	function str_limit_words($value, $words = 100, $end = '...') {
		return Str::words($value, $words, $end);
	}
}

if (!function_exists('str_plural')) {
	/**
	 * Get the plural form of an English word.
	 *
	 * @param string $value
	 * @param int $count
	 * @return string
	 */
	function str_plural($value, $count = 2) {
		return Str::plural($value, $count);
	}
}

if (!function_exists('str_random')) {
	/**
	 * Generate a more truly "random" alpha-numeric string.
	 *
	 * @param int $length
	 * @return string
	 */
	function str_random($length = 16) {
		return Str::random($length);
	}
}

if (!function_exists('str_random_quick')) {
	/**
	 * Generate a "random" alpha-numeric string. Should not be considered sufficient for cryptography, etc.
	 *
	 * @param int $length
	 * @return string
	 */
	function str_random_quick($length = 16) {
		return Str::quickRandom($length);
	}
}

if (!function_exists('str_replace_first')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Replace the first occurrence of a given value in the string.
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 * @return string
	 */
	function str_replace_first($search, $replace, $subject) {
		return Str::replaceFirst($search, $replace, $subject);
	}
}

if (!function_exists('str_replace_last')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Replace the last occurrence of a given value in the string.
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 * @return string
	 */
	function str_replace_last($search, $replace, $subject) {
		return Str::replaceLast($search, $replace, $subject);
	}
}

if (!function_exists('str_replace_array')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Replace a given value in the string sequentially with an array.
	 *
	 * @param string $search
	 * @param array $replace
	 * @param string $subject
	 * @return string
	 */
	function str_replace_array($search, $replace, $subject) {
		return Str::replaceArray($search, $replace, $subject);
	}
}

if (!function_exists('str_upper')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Convert the given string to upper-case.
	 *
	 * @param string $value
	 * @return string
	 */
	function str_upper($value) {
		return Str::upper($value);
	}
}

if (!function_exists('kebab_case')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Convert a string to kebab case.
	 *
	 * @param string $value
	 * @return string
	 */
	function kebab_case($value) {
		return Str::kebab($value);
	}
}

if (!function_exists('title_case')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Convert the given string to title case.
	 *
	 * @param string $value
	 * @return string
	 */
	function title_case($value) {
		return Str::title($value);
	}
}

if (!function_exists('str_singular')) {
	/**
	 * Get the singular form of an English word.
	 *
	 * @param string $value
	 * @return string
	 */
	function str_singular($value) {
		return Str::singular($value);
	}
}

if (!function_exists('str_slug')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param string $title
	 * @param string $separator
	 * @return string
	 */
	function str_slug($title, $separator = '-') {
		return Str::slug($title, $separator);
	}
}

if (!function_exists('snake_case')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Convert a string to snake case.
	 *
	 * @param string $value
	 * @param string $delimiter
	 * @return string
	 */
	function snake_case($value, $delimiter = '_') {
		return Str::snake($value, $delimiter);
	}
}

if (!function_exists('studly_case')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Convert a value to studly caps case.
	 *
	 * @param string $value
	 * @return string
	 */
	function studly_case($value) {
		return Str::studly($value);
	}
}

if (!function_exists('str_substring')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Returns the portion of string specified by the start and length parameters.
	 *
	 * @param string $string
	 * @param int $start
	 * @param int|null $length
	 * @return string
	 */
	function str_substring($string, $start, $length = null) {
		return Str::substr($string, $start, $length);
	}
}

if (!function_exists('str_ucfirst')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Make a string's first character uppercase.
	 *
	 * @param string $string
	 * @return string
	 */
	function str_ucfirst($string) {
		return Str::ucfirst($string);
	}
}

if (!function_exists('str_find')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Finds the position of a substring in the given string. Returns false if not found.
	 *
	 * @param string $haystack
	 * @param string|array $needles
	 * @return int|bool
	 */
	function str_find($haystack, $needles) {
		return Str::find($haystack, $needles);
	}
}

if (!function_exists('str_before')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Get the portion of a string before a given value.
	 *
	 * @param string $subject
	 * @param string $search
	 * @return string
	 */
	function str_before($subject, $search) {
		return Str::before($subject, $search);
	}
}

if (!function_exists('str_after')) {
	/**
	 * • This function is unicode friendly.
	 *
	 * Return the remainder of a string after a given value.
	 *
	 * @param string $subject
	 * @param string $search
	 * @return string
	 */
	function str_after($subject, $search) {
		return Str::after($subject, $search);
	}
}

if (!function_exists('e')) {
	/**
	 * Escape HTML special characters in a string.
	 *
	 * @param string $value
	 * @param bool $doubleEncode
	 * @return string
	 */
	function e($value, $doubleEncode = true) {
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
	}
}

if (!function_exists('preg_replace_array')) {
	/**
	 * Replace a given pattern with each value in the array in sequentially.
	 *
	 * @param string $pattern
	 * @param array $replacements
	 * @param string $subject
	 * @return string
	 */
	function preg_replace_array($pattern, array $replacements, $subject) {
		return preg_replace_callback($pattern, function () use (&$replacements) {
			foreach ($replacements as $key => $value) {
				return array_shift($replacements);
			}
		}, $subject);
	}
}

if (!function_exists('array_accessible')) {
	/**
	 * Determine whether the given value is array accessible.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	function array_accessible($value) {
		return Arr::accessible($value);
	}
}

if (!function_exists('array_add')) {
	/**
	 * Add an element to an array using "dot" notation if it doesn't exist.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	function array_add($array, $key, $value) {
		return Arr::add($array, $key, $value);
	}
}

if (!function_exists('array_collapse')) {
	/**
	 * Collapse an array of arrays into a single array.
	 *
	 * @param array $array
	 * @return array
	 */
	function array_collapse($array) {
		return Arr::collapse($array);
	}
}

if (!function_exists('array_divide')) {
	/**
	 * Divide an array into two arrays. One with keys and the other with values.
	 *
	 * @param array $array
	 * @return array
	 */
	function array_divide($array) {
		return Arr::divide($array);
	}
}

if (!function_exists('array_dot')) {
	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @param array $array
	 * @param string $prepend
	 * @return array
	 */
	function array_dot($array, $prepend = '') {
		return Arr::dot($array, $prepend);
	}
}

if (!function_exists('array_except')) {
	/**
	 * Get all of the given array except for a specified array of items.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return array
	 */
	function array_except($array, $keys) {
		return Arr::except($array, $keys);
	}
}

if (!function_exists('array_exists')) {
	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @param ArrayAccess|array $array
	 * @param string|int $key
	 * @return bool
	 */
	function array_exists($array, $key) {
		return Arr::exists($array, $key);
	}
}

if (!function_exists('array_first')) {
	/**
	 * Return the first element in an array passing a given truth test. If no truth test is provided, returns the
	 * very first element in the array.
	 *
	 * @param array $array
	 * @param callable|null $callback
	 * @param mixed $default
	 * @return mixed
	 */
	function array_first($array, $callback = null, $default = null) {
		return Arr::first($array, $callback, $default);
	}
}

if (!function_exists('array_last')) {
	/**
	 * Return the last element in an array passing a given truth test. If no truth test is provided, returns the very
	 * last element in the array.
	 *
	 * @param array $array
	 * @param callable|null $callback
	 * @param mixed $default
	 * @return mixed
	 */
	function array_last($array, $callback = null, $default = null) {
		return Arr::last($array, $callback, $default);
	}
}

if (!function_exists('array_flatten')) {
	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param array $array
	 * @param int $depth
	 * @return array
	 */
	function array_flatten($array, $depth = INF) {
		return Arr::flatten($array, $depth);
	}
}

if (!function_exists('array_forget')) {
	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return void
	 */
	function array_forget(&$array, $keys) {
		Arr::forget($array, $keys);
	}
}

if (!function_exists('array_get')) {
	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param ArrayAccess|array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function array_get($array, $key, $default = null) {
		return Arr::get($array, $key, $default);
	}
}

if (!function_exists('array_has')) {
	/**
	 * Check if an item or items exist in an array using "dot" notation.
	 *
	 * @param ArrayAccess|array $array
	 * @param string|array $keys
	 * @return bool
	 */
	function array_has($array, $keys) {
		return Arr::has($array, $keys);
	}
}

if (!function_exists('array_is_assoc')) {
	/**
	 * Determines if an array is associative. An array is "associative" if it doesn't have sequential numerical keys
	 * beginning with zero.
	 *
	 * @param array $array
	 * @return bool
	 */
	function array_is_assoc($array) {
		return Arr::isAssoc($array);
	}
}

if (!function_exists('array_only')) {
	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return array
	 */
	function array_only($array, $keys) {
		return Arr::only($array, $keys);
	}
}

if (!function_exists('array_pluck')) {
	/**
	 * Pluck an array of values from an array.
	 *
	 * @param array $array
	 * @param string|array $value
	 * @param string|array|null $key
	 * @return array
	 */
	function array_pluck($array, $value, $key = null) {
		return Arr::pluck($array, $value, $key);
	}
}

if (!function_exists('array_prepend')) {
	/**
	 * Push an item onto the beginning of an array.
	 *
	 * @param array $array
	 * @param mixed $value
	 * @param mixed $key
	 * @return array
	 */
	function array_prepend($array, $value, $key = null) {
		return Arr::prepend($array, $value, $key);
	}
}

if (!function_exists('array_pull')) {
	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function array_pull(&$array, $key, $default = null) {
		return Arr::pull($array, $key, $default);
	}
}

if (!function_exists('array_random')) {
	/**
	 * Get one or a specified number of random values from an array.
	 *
	 * @param array $array
	 * @param int|null $number
	 * @return mixed
	 *
	 * @throws InvalidArgumentException
	 */
	function array_random($array, $number = null) {
		return Arr::random($array, $number);
	}
}

if (!function_exists('array_set')) {
	/**
	 * Set an array item to a given value using "dot" notation. If no key is given to the method, the entire array will
	 * be replaced.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	function array_set(&$array, $key, $value) {
		return Arr::set($array, $key, $value);
	}
}

if (!function_exists('array_shuffle')) {
	/**
	 * Shuffle the given array and return the result.
	 *
	 * @param array $array
	 * @return array
	 */
	function array_shuffle($array) {
		return Arr::shuffle($array);
	}
}

if (!function_exists('array_sort')) {
	/**
	 * Sort the array using the given callback or "dot" notation.
	 *
	 * @param array $array
	 * @param callable|string $callback
	 * @return array
	 */
	function array_sort($array, $callback) {
		return Arr::sort($array, $callback);
	}
}

if (!function_exists('array_sort_recursive')) {
	/**
	 * Recursively sort an array by keys and values.
	 *
	 * @param array $array
	 * @return array
	 */
	function array_sort_recursive($array) {
		return Arr::sortRecursive($array);
	}
}

if (!function_exists('array_where')) {
	/**
	 * Filter the array using the given callback.
	 *
	 * @param array $array
	 * @param callable $callback
	 * @return array
	 */
	function array_where($array, $callback) {
		return Arr::where($array, $callback);
	}
}

if (!function_exists('array_wrap')) {
	/**
	 * If the given value is not an array, wrap it in one.
	 *
	 * @param mixed $value
	 * @return array
	 */
	function array_wrap($value) {
		return Arr::wrap($value);
	}
}

if (!function_exists('head')) {
	/**
	 * Returns the first element in the given array, or null if the array is empty.
	 *
	 * @param array $array
	 * @return mixed
	 */
	function head($array) {
		return reset($array);
	}
}

if (!function_exists('last')) {
	/**
	 * Returns the last element in the given array, or null if the array is empty.
	 *
	 * @param array $array
	 * @return mixed
	 */
	function last($array) {
		return end($array);
	}
}

if (!function_exists('blank')) {
	/**
	 * Determine if the given value is "blank".
	 *
	 * @param mixed $value
	 * @return bool
	 */
	function blank($value) {
		if (is_null($value)) return true;
		if (is_string($value)) return trim($value) === '';
		if (is_numeric($value) || is_bool($value)) return false;
		if ($value instanceof Countable) return count($value) === 0;

		return empty($value);
	}
}

if (!function_exists('filled')) {
	/**
	 * Determine if a value is "filled".
	 *
	 * @param mixed $value
	 * @return bool
	 */
	function filled($value) {
		return !blank($value);
	}
}

if (!function_exists('class_basename')) {
	/**
	 * Get the class "basename" of the given object / class.
	 *
	 * @param string|object $class
	 * @return string
	 */
	function class_basename($class) {
		$class = is_object($class) ? get_class($class) : $class;
		return basename(str_replace('\\', '/', $class));
	}
}

if (! function_exists('object_get')) {
	/**
	 * Get an item from an object using "dot" notation.
	 *
	 * @param object $object
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function object_get($object, $key, $default = null) {
		if (is_null($key) || trim($key) == '') {
			return $object;
		}

		foreach (explode('.', $key) as $segment) {
			if (!is_object($object) || !isset($object->{$segment})) {
				return $default;
			}

			$object = $object->{$segment};
		}

		return $object;
	}
}

if (!function_exists('windows_os')) {
	/**
	 * Determine whether the current environment is Windows based.
	 *
	 * @return bool
	 */
	function windows_os() {
		return strtolower(substr(PHP_OS, 0, 3)) === 'win';
	}
}

if (!function_exists('request')) {
	/**
	 * Returns the current request instance or an input value if the first parameter is specified.
	 *
	 * @param string|null $input
	 * @param mixed|null $default
	 * @return \Horizon\Http\Request|string|null
	 */
	function request($input = null, $default = null) {
		$request = Application::kernel()->http()->request();

		if (!is_null($input)) {
			return $request->input($input, $default);
		}

		return $request;
	}
}

if (!function_exists('response')) {
	/**
	 * Returns the current response instance or quickly writes output, response codes, or headers to the buffer.
	 *
	 * @param string|null $output
	 * @param int|null $code
	 * @param array|null $headers
	 * @return \Horizon\Http\Response
	 */
	function response($output = null, $code = null, $headers = null) {
		$response = Application::kernel()->http()->response();

		if (!is_null($output)) {
			$response->write($output);
		}

		if (!is_null($code) && is_int($code)) {
			$response->setStatusCode($code);
		}

		if (!is_null($headers) && is_array($headers)) {
			foreach ($headers as $key => $value) {
				$response->setHeader($key, $value);
			}
		}

		return $response;
	}
}

if (!function_exists('session')) {
	/**
	 * Returns the current session instance or quickly reads or writes a session value.
	 *
	 * @param string|null $name
	 * @param mixed|null $value
	 * @return \Horizon\Http\Cookie\Session|mixed
	 */
	function session($name = null, $value = null) {
		$session = request()->session();

		if (!is_null($name)) {
			if (!is_null($value)) {
				$session->put($name, $value);
				return $session;
			}

			return $session->get($name);
		}

		return $session;
	}
}

if (!function_exists('csrf_token')) {
	/**
	 * Gets the current CSRF token.
	 *
	 * @return string
	 */
	function csrf_token() {
		return session()->csrf();
	}
}

if (!function_exists('is_serialized')) {
	/**
	 * Checks if the given value is a serialized string.
	 *
	 * @param mixed $data
	 * @param bool $strict Optional. Whether to be strict about the end of the string. Default true.
	 * @return bool
	 * @see https://core.trac.wordpress.org/browser/tags/5.0.1/src/wp-includes/functions.php#L0
	 */
	function is_serialized($data, $strict = true) {
		if (!is_string($data)) return false;

		$data = trim($data);

		if ('N;' == $data) return true;
		if (strlen($data) < 4) return false;
		if (':' !== $data[1]) return false;

		if ($strict) {
			$lastc = substr($data, -1);
			if (';' !== $lastc && '}' !== $lastc) return false;
		}
		else {
			$semicolon = strpos($data, ';');
			$brace  = strpos($data, '}');

			if (false === $semicolon && false === $brace) return false;
			if (false !== $semicolon && $semicolon < 3) return false;
			if (false !== $brace && $brace < 4) return false;
		}

		$token = $data[0];

		switch ($token) {
			case 's':
				if ($strict) {
					if ('"' !== substr($data, -2, 1)) {
						return false;
					}
				}
				elseif (false === strpos($data, '"')) {
					return false;
				}
			case 'a' :
			case 'O' :
				return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
			case 'b' :
			case 'i' :
			case 'd' :
				$end = $strict ? '$' : '';
				return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
		}

		return false;
	}
}

if (!function_exists('encrypt')) {
	/**
	 * Encrypts a value using the fast-encrypt library. If the given value is not a string, it will be serialized
	 * internally, but calling decrypt() will unserialize it automatically.
	 *
	 * Important: This is a fast and ultimately insecure encryption tool. It uses variables from the current environment
	 * and server to build its encryption, but it isn't difficult to guess these variables and reverse the encryption.
	 * Use it liberally only when appropriate.
	 *
	 * @param string|mixed $value
	 * @return string
	 */
	function encrypt($value) {
		if (!is_string($value)) {
			$value = serialize($value);
		}

		return FastEncrypt::encrypt($value);
	}
}

if (!function_exists('decrypt')) {
	/**
	 * Decrypts a fast-encrypted string back into its original value. If the underlying data is serialized, it will be
	 * automatically unserialized.
	 *
	 * @param string $value
	 * @return string|mixed
	 */
	function decrypt($value) {
		$decrypted = FastEncrypt::decrypt($value);

		if (is_serialized($decrypted)) {
			$unserialized = @unserialize($decrypted);

			if ($unserialized !== false || $decrypted == "b:0;") {
				return $unserialized;
			}
		}

		return $decrypted;
	}
}

if (!function_exists('config')) {
	/**
	 * Gets the value of a configuration entry at the specified key path. The path should be in dot notation, with
	 * the first segment containing the name of the configuration file. If the file or key path does not exist, the
	 * default value is returned.
	 *
	 * @param string $key
	 * @param mixed|null $default
	 * @return mixed
	 */
	function config($key, $default = null) {
		try {
			return Application::config($key, $default);
		} catch (HorizonException $e) {
			return $default;
		}
	}
}

if (!function_exists('view')) {
	/**
	 * Renders a view in the current response object.
	 *
	 * @param string $templateFile
	 * @param array $context
	 * @return void
	 */
	function view($templateFile, array $context = array()) {
		response()->view($templateFile, $context);
	}
}

if (!function_exists('redirect')) {
	/**
	 * Redirects the current response object.
	 *
	 * @param string $to
	 * @param int $code
	 * @param bool $halt
	 * @return false
	 */
	function redirect($to = null, $code = 302, $halt = true) {
		if ($halt) response()->halt();
		response()->redirect($to, $code);

		return false;
	}
}

if (!function_exists('__')) {
	/**
	 * Translates the specified text using the language bucket from the kernel, replacing the provided variables if
	 * applicable. If no translation is available, returns the provided text (with variables still replaced).
	 *
	 * @param string $text
	 * @param array $variables
	 * @return string
	 */
	function __($text, $variables = array()) {
		return Application::kernel()->translation()->bucket()->translate($text, $variables);
	}
}

if (!function_exists('translate')) {
	/**
	 * Translates the specified text using the language bucket from the kernel, replacing the provided variables if
	 * applicable. If no translation is available, returns the provided text (with variables still replaced).
	 *
	 * @param string $text
	 * @param array $variables
	 * @return string
	 */
	function translate($text, $variables = array()) {
		return __($text, $variables);
	}
}

if (!function_exists('bucket')) {
	/**
	 * Gets the global language bucket instance.
	 *
	 * @return \Horizon\Translation\LanguageBucket
	 */
	function bucket() {
		return Application::kernel()->translation()->bucket();
	}
}

if (!function_exists('is_octal')) {
	/**
	 * Determines if the integer is an octal.
	 *
	 * @param int $int
	 * @return bool
	 */
	function is_octal($int) {
		return decoct(octdec($int)) == $int;
	}
}

if (!function_exists('abort')) {
	/**
	 * Terminates the page. Equivalent to die(), but it gives the kernel a chance for any last-minute work.
	 *
	 * @param int $code Exit code.
	 * @return false
	 */
	function abort($code = 0) {
		Application::kernel()->shutdown($code);
		return false;
	}
}

if (!function_exists('base_path')) {
	/**
	 * Gets the absolute path to the project's root directory.
	 *
	 * @return string
	 */
	function base_path() {
		return Application::root();
	}
}

if (!function_exists('config_path')) {
	/**
	 * Gets the absolute path to the `config` directory.
	 *
	 * @return string
	 */
	function config_path() {
		return Application::paths()->config();
	}
}

if (!function_exists('public_path')) {
	/**
	 * Gets the absolute path to the `public` directory.
	 *
	 * @return string
	 */
	function public_path() {
		return Application::paths()->public();
	}
}

if (!function_exists('path')) {
	/**
	 * Returns the absolute path given a relative path within the root project directory.
	 *
	 * @param string $relative
	 * @return string
	 */
	function path($relative = '') {
		return Application::root($relative);
	}
}

if (!function_exists('asset')) {
	/**
	 * Returns a partial URL to the given asset within the public directory, taking into consideration the current
	 * routing mode. This does not return a full URL (i.e. there is no sceme or host) and the path starts with '/'.
	 *
	 * @param string $relativePath
	 * @return string
	 */
	function asset($relativePath) {
		return Application::asset($relativePath);
	}
}

if (!function_exists('asset_url')) {
	/**
	 * Returns the absolute URL with the current scheme to the relative asset path in the public directory.
	 *
	 * @param string $relativePath
	 * @return string
	 */
	function asset_url($relativePath) {
		$uri = asset($relativePath);
		$url = request()->url($uri);

		return $url;
	}
}

if (!function_exists('secure_asset_url')) {
	/**
	 * Returns the absolute URL with the HTTPS scheme to the relative asset path in the public directory.
	 *
	 * @param string $relativePath
	 * @return string
	 */
	function secure_asset_url($relativePath) {
		$uri = asset($relativePath);
		$url = request()->url($uri);

		$parts = parse_url($url);

		return sprintf(
			'https://%s%s',
			$parts['host'],
			$parts['path']
		);
	}
}

if (!function_exists('resolve')) {
	/**
	 * Returns a singleton of the given class name from the last service provider that registered it.
	 *
	 * @param string $className
	 * @return object|null
	 */
	function resolve($className) {
		return Application::container()->make($className);
	}
}

if (!function_exists('app')) {
	/**
	 * Returns the application's service container.
	 *
	 * @return Container
	 */
	function app() {
		return Application::container();
	}
}

if (!function_exists('report')) {
	/**
	 * Reports and, if enabled, logs the given exception using the current error handler. If `$forceLogging` is set
	 * to true, then the exception will always be logged regardless of the app's logging configuration.
	 *
	 * @param Exception $ex
	 * @param bool $forceLogging
	 * @return void
	 */
	function report(Exception $ex, $forceLogging = false) {
		$error = HorizonError::fromException($ex);

		$handler = ErrorMiddleware::getErrorHandler();
		$handler->report($error);

		if (ErrorMiddleware::canLog($error) || $forceLogging) {
			$handler->log($error);
		}
	}
}

if (!function_exists('env')) {
	/**
	 * Returns the value of the specified environment variable.
	 *
	 * This function reads the `.env` or `env.php` file in the project root if the specified variable is not found.
	 * The variable name is case insensitive. The return type will be determined based on the given default value
	 * (always a string if the default is null).
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	function env($name, $default = null) {
		return Environment::get($name, $default);
	}
}

if (!function_exists('setenv')) {
	/**
	 * Sets the value of the specified environment variable.
	 *
	 * Warning: This is not to be confused with the native `putenv` function. This function does not actually change
	 * the environment variables, it only registers them internally in Horizon's environment manager.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	function setenv($name, $value = null) {
		Environment::set($name, $value);
	}
}

if (!function_exists('delenv')) {
	/**
	 * Deletes the specified environment variable.
	 *
	 * @param string $name
	 * @return void
	 */
	function delenv($name) {
		Environment::delete($name);
	}
}

if (!function_exists('is_mode')) {
	/**
	 * Returns if the environment matches the given mode.
	 *
	 * This looks at the `APP_MODE` environment variable which typically should be either `production` or `development`.
	 *
	 * @param string $mode
	 * @return bool
	 */
	function is_mode($mode) {
		return Application::mode() === $mode;
	}
}

if (!function_exists('datetime_to_timestamp')) {
	/**
	 * Converts a SQL `DATETIME` value to a unix seconds timestamp with an optional timezone.
	 *
	 * Passing a timezone into the second parameter will override the application's global timezone as configured in
	 * your `config/app.php` file. We recommend using a consistent timezone across your application and database.
	 *
	 * @param string $datetime
	 * @param string|null $timezone
	 * @return int
	 */
	function datetime_to_timestamp($datetime, $timezone = null) {
		if ($timezone === null) {
			$timezone = config('app.timezone');
		}

		$timezone = new DateTimeZone($timezone);
		$time = new DateTime($datetime, $timezone);
		return $time->getTimestamp();
	}
}

if (!function_exists('timestamp_to_datetime')) {
	/**
	 * Converts a unix seconds timestamp to a SQL `DATETIME` value with an optional timezone.
	 *
	 * Passing a timezone into the second parameter will override the application's global timezone as configured in
	 * your `config/app.php` file. We recommend using a consistent timezone across your application and database.
	 *
	 * @param int $timestamp
	 * @param string|null $timezone
	 * @return string
	 */
	function timestamp_to_datetime($timestamp, $timezone = null) {
		$date = DateTime::createFromFormat('U', $timestamp);

		if ($timezone === null) {
			$timezone = config('app.timezone');
		}

		$date->setTimezone(new DateTimeZone($timezone));
		return $date->format('Y-m-d H:i:s');
	}
}

if (!function_exists('ref')) {
	/**
	 * Returns a reference to a database table field.
	 *
	 * @param string $fieldName
	 * @return ColumnReference
	 */
	function ref($fieldName) {
		return new ColumnReference($fieldName);
	}
}
