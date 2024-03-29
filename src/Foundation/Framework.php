<?php

namespace Horizon\Foundation;

use Horizon\Support\Path;

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
			$path = Framework::path('composer.json');
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
		$lock = Application::lock();

		if (!is_null($lock)) {
			foreach ($lock->packages as $package) {
				if ($package->name === "baileyherbert/horizon") {
					return $package->version;
				}
			}
		}

		return 'dev-master';
	}

	/**
	 * Gets the absolute path to the root directory of the framework.
	 *
	 * **WARNING!** This returns a path inside the framework's vendor directory, NOT within your application. To get a
	 * path from the application root, use `Application::root()` or `Application::paths()`.
	 *
	 * @param string|null $relative
	 * @return string
	 */
	public static function path($relative = null) {
		return Path::join(dirname(dirname(__DIR__)), $relative);
	}

	/**
	 * Gets the current environment mode. If the first argument contains a string, returns a boolean representing
	 * whether the provided string matches the environment or not (case-insensitive).
	 *
	 * Possible environments are ('web', 'test', 'console').
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
