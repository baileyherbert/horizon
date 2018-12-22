<?php

namespace Horizon\Support;

class Arr
{

    /**
     * Checks if a case-insensitive key exists in the array.
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function hasKey(array &$array, $key)
    {
        if (is_numeric($key)) {
            foreach ($array as $i => $v) {
                if ($i === $key) {
                    return true;
                }
            }
        }

        elseif (is_string($key)) {
            foreach ($array as $i => $v) {
                if (is_string($i) && strcasecmp($i, $key) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if a value exists in the array with case-insensitive string matching.
     *
     * @param array $array
     * @param mixed $value
     *
     * @return bool
     */
    public static function exists(array &$array, $value)
    {
        if (is_string($value)) {
            foreach ($array as $i => $v) {
                if (is_string($v) && strcasecmp($v, $value) === 0) {
                    return true;
                }
            }
        }

        else {
            foreach ($array as $i => $v) {
                if ($i == $value) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets the value of a case-insensitive key in the array.
     *
     * @param array $array
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public static function &get(array &$array, $key = null, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (is_string($key)) {
            foreach ($array as $i => $v) {
                if (is_string($i) && strcasecmp($i, $key) === 0) {
                    return $v;
                }
            }
        }

        else {
            foreach ($array as $i => $v) {
                if ($i == $key) {
                    return $v;
                }
            }
        }

        return $default;
    }

    /**
     * Gets the last element from the specified array, or returns null if it is empty or not associative.
     *
     * @param array
     * @return mixed|null
     */
    public static function &last(array &$array)
    {
        if (empty($array)) {
            return null;
        }

        $offset = count($array) - 1;

        if (!isset($array[$offset])) {
            return null;
        }

        return $array[$offset];
    }

    /**
     * Flattens a multi-dimensional array to a two-dimensional array with keys in dot notation.
     *
     * @param array $item
     * @param string $keyTransformCallable
     * @param string $context
     * @return array
     */
    public static function &flatten(&$item, $keyTransformCallable = null, $context = '')
    {
        $flattened = array();

        foreach ($item as $key => $value) {
            if (is_array($value) || is_object($value)) {
                foreach(static::flatten($value, null, "$context$key.") as $contextualKey => $iValue) {
                    if (!is_null($keyTransformCallable)) {
                        $contextualKey = $keyTransformCallable($contextualKey);
                    }

                    $flattened[$contextualKey] = $iValue;
                }
            }
            else {
                $contextualKey = $context . $key;

                if (!is_null($keyTransformCallable)) {
                    $contextualKey = $keyTransformCallable($contextualKey);
                }

                $flattened[$contextualKey] = $value;
            }
        }

        return $flattened;
    }

}
