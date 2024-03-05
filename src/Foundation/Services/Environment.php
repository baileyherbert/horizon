<?php

namespace Horizon\Foundation\Services;

use Exception;

/**
 * Utility class for parsing and querying environment variables.
 */
class Environment {

	private static $fileLoaded = false;
	private static $processLoaded = false;

	/**
	 * Environment variables loaded from env files.
	 *
	 * @var string[]
	 */
	private static $fileData = array();

	/**
	 * Environment variables loaded from the process env vars.
	 *
	 * @var string[]
	 */
	private static $processData = array();

	/**
	 * Environment variables that have been manually set from within the application ("overrides").
	 *
	 * @var string[]
	 */
	private static $overrideData = array();

	/**
	 * Returns the value of the specified environment variable (case-insensitive) or falls back to the given default.
	 *
	 * The type of the returned value will depend on the type of the given default value.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($name, $default = null) {
		$name = strtolower($name);

		if (array_key_exists($name, static::$overrideData)) {
			return static::formatWithDefault($name, static::$overrideData[$name], $default);
		}

		if (array_key_exists($name, static::getProcessData())) {
			return static::formatWithDefault($name, static::getProcessData()[$name], $default);
		}

		if (array_key_exists($name, static::getFileData())) {
			return static::formatWithDefault($name, static::getFileData()[$name], $default);
		}

		return $default;
	}

	/**
	 * Sets the value of the specified environment variable in the override register.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public static function set($name, $value) {
		$name = strtolower($name);
		static::$overrideData[$name] = $value;
	}

	/**
	 * Deletes the specified environment variables from all registers.
	 *
	 * If the `$useOverridesRegister` parameter is true, then the variable is only removed from the override register.
	 *
	 * @param string $name
	 * @param bool $useOverridesRegister
	 * @return void
	 */
	public static function delete($name, $useOverridesRegister = false) {
		$name = strtolower($name);

		if (isset(static::$overrideData[$name])) {
			unset(static::$overrideData[$name]);
		}

		if (!$useOverridesRegister && isset(static::$processData[$name])) {
			unset(static::$processData[$name]);
		}

		if (!$useOverridesRegister && isset(static::$fileData[$name])) {
			unset(static::$fileData[$name]);
		}
	}

	/**
	 * Returns the given value converted to a boolean or number depending on the type of `$default`.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param mixed $default
	 * @return mixed
	 */
	private static function formatWithDefault($name, $value, $default) {
		switch (gettype($default)) {
			case 'boolean': {
				return in_array(
					strtolower($value),
					array('true', '1', 'yes', 'y', 't', 'on')
				);
			}

			case 'integer':
			case 'double': {
				if (is_numeric($value)) {
					return strpos($value, '.') === false ? intval($value) : floatval($value);
				}

				throw new Exception('Environment variable "' . strtoupper($name) . '" requires a numeric value');
			}
		}

		return $value;
	}

	/**
	 * Returns environment variables loaded from the disk.
	 */
	private static function getFileData() {
		if (!static::$fileLoaded) {
			static::readEnvFile();
			static::readEnvArrayFile();
			static::$fileLoaded = true;
		}

		return static::$fileData;
	}

	/**
	 * Returns environment variables loaded into the current process.
	 */
	private static function getProcessData() {
		if (!static::$processLoaded) {
			foreach ($_ENV as $key => $value) {
				static::$processData[strtolower($key)] = $value;
			}

			static::$processLoaded = true;
		}

		return static::$processData;
	}

	/**
	 * Reads the `.env` files if available.
	 *
	 * @return void
	 */
	private static function readEnvFile() {
		$targetFiles = array(
			path('.env')
		);

		foreach ($targetFiles as $targetFilePath) {
			if (file_exists($targetFilePath) && is_readable($targetFilePath)) {
				static::injectDotEnvString(file_get_contents($targetFilePath));
			}
		}
	}

	/**
	 * Parses and loads environment variables from a string in dotenv format.
	 *
	 * @param string $data
	 * @return void
	 */
	private static function injectDotEnvString($data) {
		$contents = preg_split("/\r\n|\n|\r/", trim($data));

		foreach ($contents as $lineNumber => $line) {
			$line = trim($line);
			$splitIndex = strpos($line, '=');

			if (empty($line) || starts_with($line, '#')) {
				continue;
			}

			if ($splitIndex === false) {
				throw new Exception("Error parsing .env file on line $lineNumber");
			}

			$key = strtolower(substr($line, 0, $splitIndex));
			$value = substr($line, $splitIndex + 1);

			static::$fileData[$key] = $value;
		}
	}

	/**
	 * Reads the `env.php` files if available.
	 *
	 * @return void
	 */
	private static function readEnvArrayFile() {
		$targetFiles = array(
			path('.env.php')
		);

		foreach ($targetFiles as $targetFilePath) {
			if (file_exists($targetFilePath) && is_readable($targetFilePath)) {
				$contents = require $targetFilePath;

				if (is_string($contents)) {
					static::injectDotEnvString($contents);
					continue;
				}

				if (!is_array($contents)) {
					throw new Exception('Expected env.php file to return array, got ' . gettype($contents));
				}

				foreach ($contents as $key => $value) {
					$key = strtolower($key);

					if (!is_string($value)) {
						$value = static::convertValueToString($value);
					}

					if (!is_null($value)) {
						static::$fileData[$key] = $value;
					}
				}
			}
		}
	}

	/**
	 * Converts the given value into a string (or null if invalid).
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	private static function convertValueToString($value) {
		switch (gettype($value)) {
			case 'boolean': {
				return $value ? 'true' : 'false';
			}
			case 'integer':
			case 'double': {
				return strval($value);
			}
		}

		return null;
	}

}
