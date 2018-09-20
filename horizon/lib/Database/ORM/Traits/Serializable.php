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

                    if (!(is_object($returned) && self::isRelationship($returned))) {
                        continue;
                    }

                    $results = $returned->get();
                    $converted = array();
                    $isSkipped = false;

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
