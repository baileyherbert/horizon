<?php

namespace Horizon\Utils;

class Str
{

    /**
     * Joins two or more strings together by spaces, ignoring blank or empty strings (or those only consisting of
     * whitespace), and preventing duplicate spaces.
     *
     * @param string $one
     * @param string $two,...
     * @return string
     */
    public static function join()
    {
        $segments = func_get_args();

        if (count($segments) == 1 && is_array($segments[0])) {
            $segments = $segments[0];
        }

        $string = trim(array_shift($segments));

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if (!empty($segment)) {
                $string .= ' ' . $segment;
            }
        }

        return $string;
    }

    /**
     * Checks whether the $haystack starts with the $needle. This is sensitive to character casing and whitespace.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        if (is_string($needle)) {
            return (substr($haystack, 0, strlen($needle))) == $needle;
        }

        if (is_array($needle)) {
            foreach ($needle as $n) {
                if ((substr($haystack, 0, strlen($n))) == $n) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks whether the $haystack ends with the $needle. This is sensitive to character casing and whitespace.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return (substr($haystack, -strlen($needle))) == $needle;
    }

    /**
     * Strips $needle from the beginning of $haystack if it's there and returns the new string.
     *
     * @param string $haystack
     * @param string $needle
     * @return string
     */
    public static function stripBeginning($haystack, $needle)
    {
        if (static::startsWith($haystack, $needle)) {
            return substr($haystack, strlen($needle));
        }

        return $haystack;
    }

}
