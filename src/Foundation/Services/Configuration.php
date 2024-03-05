<?php

namespace Horizon\Foundation\Services;

use Horizon\Exception\HorizonException;
use Horizon\Foundation\Application;
use Horizon\Support\Profiler;

/**
 * Utility class which loads configuration.
 */
class Configuration {

	private static $config = array();

	/**
	 * Gets the value of the specified configuration key from the app\config directory.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @throws HorizonException
	 * @return mixed
	 */
	public static function get($key, $default = null) {
		// Parse the key
		$configFile = self::getConfigFileName($key);
		$segments = self::getConfigFileSegments($key);

		// Load the configuration file if it isn't cached
		if (!array_key_exists($configFile, self::$config)) {
			static::loadConfigurationFile($configFile);
		}

		// Get the configuration as an array
		$config = self::$config[$configFile];

		// Return the desired value
		return self::getConfigValue($config, $segments, $default);
	}

	/**
	 * Iterates a configuration array to find the value of a namespaced config key (separated by periods).
	 *
	 * @param array $config
	 * @param array $segments
	 * @param mixed $default
	 * @return mixed
	 */
	private static function getConfigValue(array &$config, array &$segments, &$default) {
		$pointer = $config;

		foreach ($segments as $name) {
			if (array_key_exists($name, $pointer)) {
				$pointer = $pointer[$name];
			}
			else {
				return $default;
			}
		}

		return $pointer;
	}

	/**
	 * Loads a configuration file with the specified name. The name must not include '.php' and loads from the
	 * app's config directory.
	 *
	 * @param string $name
	 * @throws HorizonException
	 * @return void
	 */
	public static function loadConfigurationFile($name) {
		$start = microtime(true);
		$path = Application::paths()->configDir($name . '.php');
		$relative = Application::paths()->getRelative($path);

		// Throw an exception if the file doesn't exist
		if (!file_exists($path)) {
			throw new HorizonException(0x0002, $path);
		}

		// Require the file - it should return an array
		$config = require $path;

		// Verify type array
		if (!is_array($config)) {
			throw new HorizonException(0x0003, $path);
		}

		// Store the array
		self::$config[$name] = $config;

		// Profiling
		$took = microtime(true) - $start;
		Profiler::recordAsset('Configuration files', $relative, $took);
	}

	/**
	 * Extracts the name of the config file (the first segment) from a namespaced config key.
	 *
	 * @param string $path
	 * @return string
	 */
	private static function getConfigFileName($path) {
		$root = $path;

		if (strpos($path, '.') !== false) {
			$root = strtok($path, '.');
		}

		elseif (strpos($path, '/') !== false) {
			$root = strtok($path, '/');
		}

		return trim($root);
	}

	/**
	 * Extracts the segments, not including the config file name, of the namespaced config key.
	 *
	 * @param string $path
	 * @return string[]
	 */
	private static function getConfigFileSegments($path) {
		$root = self::getConfigFileName($path) . '.';
		$path = trim(substr($path, strlen($root)), '/.');

		if (strlen($path) == 0) {
			return array();
		}

		if (strpos($path, '.') >= 0) {
			return explode('.', $path);
		}

		if (strpos($path, '/') >= 0) {
			return explode('/', $path);
		}

		return array($path);
	}

}
