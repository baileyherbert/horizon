<?php

namespace Horizon\Foundation;

/**
 * Utility class for getting information about the framework.
 * @internal
 */
class Framework {

	/**
	 * @var object
	 */
	private static $composer;

	/**
	 * Gets the decoded composer.json object for the framework.
	 *
	 * @return object
	 */
	public static function composer() {
		if (is_null(static::$composer)) {
			$path = Application::path('horizon/composer.json');
			static::$composer = json_decode(file_get_contents($path));
		}

		return static::$composer;
	}

	/**
	 * Gets the current version of the framework (format x.x.x).
	 *
	 * @return string
	 */
	public static function version() {
		return static::composer()->version;
	}

	/**
	 * Gets the current edition of the framework.
	 *
	 * @return string
	 */
	public static function edition() {
		$name = static::composer()->name;

		if (preg_match('/[a-z]+\/([a-z]+)/', $name, $matches)) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Gets the absolute path to the root directory of the framework. If a $relative path is passed as the first
	 * argument, the returned path will include the relative path.
	 *
	 * @param string|null $relative
	 * @return string
	 */
	public static function path($relative = null) {
		return Application::path($relative);
	}

	/**
	 * Gets the current environment mode. If the first argument contains a string, returns a boolean representing
	 * whether the provided string matches the environment or not (case-insensitive).
	 *
	 * Possible environments are ('production', 'test', 'console').
	 *
	 * @param string $matches
	 * @return string|bool
	 */
	public static function environment($matches = null) {
		$value = Application::environment();

		// Test against $matches argument
		if (!is_null($matches)) {
			return strcasecmp($value, $matches) === 0;
		}

		// Return environment mode
		return $value;
	}

}
