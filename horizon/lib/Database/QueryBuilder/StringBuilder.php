<?php

namespace Horizon\Database\QueryBuilder;

class StringBuilder
{

	/**
	 * Formats the table name for use in a query.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function formatTableName($name)
	{
		$name = trim($name);
		$segments = explode('.', $name);

		if (count($segments) == 1) {
			return sprintf('`%s`', $segments[0]);
		}
		elseif (count($segments) == 2) {
			return sprintf('`%s`.%s', $segments[0], static::formatTableName($segments[1]));
		}

		return $name;
	}

	/**
	 * Formats the column name for use in a query.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function formatColumnName($name)
	{
		$name = trim($name);
		$segments = explode('.', $name);

		if ($name == '*') {
			return '*';
		}

		if (count($segments) == 1) {
			return sprintf('`%s`', $segments[0]);
		}
		elseif (count($segments) == 2) {
			return sprintf('`%s`.%s', $segments[0], static::formatColumnName($segments[1]));
		}

		return $name;
	}

	/**
	 * Formats an operator symbol or keyword for use in a query.
	 *
	 * @param string $op
	 * @return string
	 */
	public static function formatOperator($op)
	{
		if (strlen($op) == 1) {
			return $op;
		}

		return strtoupper(trim($op));
	}

	/**
	 * Determines if an operator is a function.
	 *
	 * @param string $text
	 * @return bool
	 */
	public static function isFunction($text)
	{
		if (is_string($text)) {
			return 1 == preg_match('/^[A-Z]+(\([^)]*\))$/', $text);
		}

		if (is_array($text) && count($text) > 0) {
			return static::isFunction($text[0]);
		}

		return false;
	}

	/**
	 * Escapes a value.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function escapeEnumValue($value)
	{
		if (is_numeric($value)) {
			return $value;
		}

		if (is_null($value) || $value == 'NULL') {
			return 'NULL';
		}

		$value = addcslashes($value, "'");
		$value = str_replace("\\\'", "\'", $value);

		return "'$value'";
	}

	/**
	 * Formats a value for use in an AGAINST (...) full-text query segment. The returned string contains surrounding
	 * single quotes.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function formatFulltextValue($value)
	{
		$value = str_replace("'", "\\'", $value);
		return sprintf("'%s'", $value);
	}

	/**
	 * Generates a string of types (such as isssissddsdsii) from an array. Useful for prepared statement automation.
	 *
	 * @param array $bindings
	 * @return string
	 */
	public static function generateTypes(array &$bindings)
	{
		$types = array();

		foreach ($bindings as $value) {
			if (is_numeric($value) && strpos($value, '.') === false) $types[] = 'i';
			else if (is_numeric($value)) $types[] = 'd';
			else $types[] = 's';
		}

		return implode('', $types);
	}

	/**
	 * Computes the name of a mapping table to form a many-to-many relationship. Expects the fully-qualified names of
	 * model classes. Always returns the tables in alphabetical order (first in alphabet = first in map table name).
	 *
	 * @param string $singular
	 * @param string $plural
	 * @return string
	 */
	public static function generateMappingTableName($model, $relatedModel)
	{
		$names = array($model, $relatedModel);

		foreach ($names as $i => $value) {
			$model = strtolower(substr(strrchr($value, '\\'), 1));

			if (substr($model, -1) == 's') {
				$model = substr($model, 0, -1);
			}

			$names[$i] = $model;
		}

		sort($names);

		return implode('_', $names);
	}

	/**
	 * Gets the singular form of a model's name. Such as 'user' for Users.
	 *
	 * @param string|Model $model
	 * @return string
	 */
	public static function getSingularModelName($model)
	{
		if (is_object($model)) {
			$model = get_class($model);
		}

		$model = strtolower(substr(strrchr($model, '\\'), 1));

		if (substr($model, -1) == 's') {
			$model = substr($model, 0, -1);
		}

		return $model;
	}

}
