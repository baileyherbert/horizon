<?php

namespace Horizon\Database\ORM\Traits;

use Horizon\Database\Model;

use Horizon\Database\ORM\Relationship;

/**
 * Implements serialization into a model instance.
 */
trait Serializable
{

	/**
	 * An array containing the columns to hide from serialization. When hiding relationships, use the relationship's
	 * method name.
	 *
	 * @var string[]
	 */
	protected $hidden = array();

	/**
	 * An array containing the columns to include in serialization. When hiding relationships, use the relationship's
	 * method name.
	 *
	 * @var string[]
	 */
	protected $visible = array();

	/**
	 * An associative array pointing columns to internal private methods which are used to generate output. An example is
	 * pointing a date column to an internal dateFormat column to generate a user-friendly timestamp.
	 *
	 * @var array
	 */
	protected $casts = array();

	/**
	 * An array containing column names. If specified, the columns in the serialization output will follow the same order
	 * from top to bottom. Any columns not included will resume their normal order, after specified columns.
	 *
	 * @var string[]
	 */
	protected $order = array();

	/**
	 * Tests whether the column or relationship name can be serialized.
	 *
	 * @param string $name
	 * @return bool
	 */
	protected function isColumnSerializable($name)
	{
		if (!empty($this->visible)) {
			if (!in_array($name, $this->visible)) {
				return false;
			}
		}
		else if (!empty($this->hidden)) {
			if (in_array($name, $this->hidden)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Tests whether the object is a relationship.
	 *
	 * @param object $object
	 * @return bool
	 */
	protected function isRelationship($object)
	{
		return ($object instanceof Relationship);
	}

	/**
	 * Gets an array of variables and their values, which can be seralized.
	 *
	 * @param string[] $skipped
	 * @return array
	 */
	protected function getSerializeData($skipped = array())
	{
		$permitted = array();

		// Columns
		foreach ($this->storage as $name => $value) {
			if ($this->isColumnSerializable($name)) {
				$getterName = '__get' . str_replace('_', '', $name);

				if (method_exists($this, $getterName)) {
					$value = $this->$getterName($value);
				}

				if (is_numeric($value)) {
					$value = doubleval($value);
				}

				$permitted[$name] = $value;
			}
		}

		// Relationships
		$className = get_class($this);
		$class = new \ReflectionClass($className);
		$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
		$skipped[] = $className;

		foreach ($methods as $method) {
			if ($method->class == $className && $method->getNumberOfParameters() === 0) {
				if ($this->isColumnSerializable($method->name)) {
					$returned = $method->invoke($this);

					if (!(is_object($returned) && $this->isRelationship($returned))) {
						continue;
					}

					$results = $returned->get();
					$converted = array();
					$isSkipped = false;

					if (is_null($results)) {
						$converted = null;
					}

					if (is_array($results)) {
						foreach ($results as $key => $result) {
							if ($result instanceof Model && !$result->equals($this)) {
								$modelClass = get_class($result);

								if (in_array($modelClass, $skipped)) {
									$isSkipped = true;
									continue;
								}

								$converted[$key] = $result->toArray($skipped);
							}
						}
					}
					else if ($results instanceof Model && !$results->equals($this)) {
						$modelClass = get_class($results);

						if (in_array($modelClass, $skipped)) {
							$isSkipped = true;
							continue;
						}

						$converted = $results->toArray($skipped);
					}

					if (!$isSkipped) {
						$permitted[($method->name)] = $converted;
					}
				}
			}
		}

		// Serialization converters
		$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);

		foreach ($methods as $method) {
			if (preg_match('/^serialize(\w+)$/i', $method->name, $matches)) {
				$propName = $matches[1];
				$fullName = 'serialize' . $propName;
				$found = false;

				foreach ($permitted as $i => $v) {
					if (strcasecmp($i, $propName) === 0) {
						$permitted[$i] = call_user_func(array($this, $fullName), $this, $v);
						$found = true;
						break;
					}
				}

				if (!$found) {
					$propNameLower = lcfirst($propName);
					$permitted[$propNameLower] = call_user_func(array($this, $fullName), $this, null);
				}
			}
		}

		// Reorder columns
		if (!empty($this->order)) {
			$ordered = array();

			foreach ($this->order as $colName) {
				foreach ($permitted as $i => $v) {
					if (strcasecmp($i, $colName) === 0) {
						$ordered[$i] = $v;
					}
				}
			}

			foreach ($permitted as $i => $v) {
				if (!isset($ordered[$i])) {
					$ordered[$i] = $v;
				}
			}

			$permitted = $ordered;
		}

		return $permitted;
	}

	/**
	 * Converts the object to a JSON string.
	 *
	 * @return string
	 */
	public function toJson()
	{
		$data = $this->getSerializeData();

		return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Converts the object to an array.
	 *
	 * @param string[] $hidden Classes of models to hide during serialization.
	 * @return array
	 */
	public function toArray($hidden = array())
	{
		return $this->getSerializeData($hidden);
	}

}
