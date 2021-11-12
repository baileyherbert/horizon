<?php

namespace Horizon\Database\ORM;

use Horizon\Database\Model;
use ReflectionClass;

/**
 * This class parses docblock comments from model classes to determine their fields and corresponding types.
 */
class DocParser {

	private static $cache = array();

	private $readProperties = array();
	private $writeProperties = array();

	/**
	 * Constructs a new `DocParser` instance for the given class name.
	 *
	 * @param string $className
	 */
	public function __construct($className) {
		$reflection = new ReflectionClass($className);
		$traits = $reflection->getTraits();

		foreach ($traits as $trait) {
			$this->parseDocComment($trait->getName());
		}

		$this->parseDocComment($className);
	}

	/**
	 * Parses the doc block comment for the given class.
	 *
	 * @param string $className
	 * @return void
	 */
	private function parseDocComment($className) {
		$reflection = new ReflectionClass($className);
		$doc = $reflection->getDocComment();
		$doc = preg_replace("/^[ \t]*\*[ \t]*/m", "", $doc);
		$docLines = preg_split("/\r?\n/", $doc);

		unset($docLines[0]);
		unset($docLines[count($docLines)]);

		$this->parseProperties($docLines);
	}

	/**
	 * Parses properties from the given lines.
	 *
	 * @param string[] $lines
	 * @return void
	 */
	private function parseProperties(array $lines) {
		foreach ($lines as $line) {
			if (preg_match('/^@property(?:-(read|write))?[ \t]+([^ \t]+)[ \t]+\$([^ \t\r\n]+)/m', $line, $matches)) {
				$method = $matches[1];
				$fieldTypes = preg_split("/ *\| */", $matches[2]);
				$fieldName = strtolower($matches[3]);

				switch ($method) {
					case '': {
						$this->readProperties[$fieldName] = $fieldTypes;
						$this->writeProperties[$fieldName] = $fieldTypes;
						break;
					}

					case 'read': {
						$this->readProperties[$fieldName] = $fieldTypes;
						break;
					}

					case 'write': {
						$this->writeProperties[$fieldName] = $fieldTypes;
						break;
					}
				}
			}
		}
	}

	/**
	 * Returns `true` if the parser found the specified field.
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	public function hasField($fieldName) {
		$fieldName = strtolower($fieldName);

		return array_key_exists($fieldName, $this->readProperties) ||
			array_key_exists($fieldName, $this->writeProperties);
	}

	/**
	 * Returns the type that the specified field can read. Returns `null` if the field is not found.
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function getReadTypes($fieldName) {
		$fieldName = strtolower($fieldName);

		if (array_key_exists($fieldName, $this->readProperties)) {
			return $this->readProperties[$fieldName];
		}
	}

	/**
	 * Returns an array of types that the specified field can write. Returns `null` if the field is not found.
	 *
	 * @param string $fieldName
	 * @return string[]
	 */
	public function getWriteType($fieldName) {
		$fieldName = strtolower($fieldName);

		if (array_key_exists($fieldName, $this->writeProperties)) {
			return $this->writeProperties[$fieldName];
		}
	}

	/**
	 * Returns the `DocParser` instance for the given model, or creates one if necessary.
	 *
	 * @param Model|string $model
	 * @return static
	 */
	public static function get($model) {
		if (is_object($model)) {
			$model = get_class($model);
		}

		if (!isset(static::$cache[$model])) {
			static::$cache[$model] = new static($model);
		}

		return static::$cache[$model];
	}

}
