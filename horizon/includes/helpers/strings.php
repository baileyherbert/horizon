<?php

use Horizon\Support\Str;

if (!function_exists('camel_case')) {
    /**
     * [Unicode Friendly]
     *
     * Convert a value to camel case.
     *
     * @param string $value
     * @return string
     */
    function camel_case($value)
    {
        return Str::camel($value);
    }
}

if (!function_exists('str_join')) {
    /**
     * Joins two or more strings together by spaces, ignoring blank or empty strings (or those only consisting of
     * whitespace), and preventing duplicate spaces.
     *
     * @param string $one
     * @param string $two,...
     * @return string
     */
    function str_join()
    {
        return forward_static_call_array(array('Str', 'join'), func_get_args());
    }
}

if (!function_exists('starts_with')) {
    /**
     * [Unicode Friendly]
     *
     * Determine if a given string starts with a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @param bool $ignoreCase
     * @return bool
     */
    function starts_with($haystack, $needles, $ignoreCase = false)
    {
        if ($ignoreCase) {
            return Str::startsWithIgnoreCase($haystack, $needles);
        }

        return Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('ends_with')) {
    /**
     * [Unicode Friendly]
     *
     * Determine if a given string ends with a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @param bool $ignoreCase
     * @return bool
     */
    function ends_with($haystack, $needles, $ignoreCase = false)
    {
        if ($ignoreCase) {
            return Str::endsWithIgnoreCase($haystack, $needles);
        }

        return Str::endsWith($haystack, $needles);
    }
}

if (!function_exists('str_contains')) {
    /**
     * [Unicode Friendly]
     *
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
    }
}

if (!function_exists('str_finish')) {
    /**
     * Cap a string with a single instance of a given value.
     *
     * @param string $value
     * @param string $cap
     * @return string
     */
    function str_finish($value, $cap)
    {
        return Str::finish($value, $cap);
    }
}

if (!function_exists('str_is')) {
    /**
     * [Unicode Friendly]
     *
     * Determine if a given string matches a given pattern.
     *
     * @param string|array $pattern
     * @param string $value
     * @return bool
     */
    function str_is($pattern, $value)
    {
        return Str::is($pattern, $value);
    }
}

if (!function_exists('str_length')) {
    /**
     * [Unicode Friendly]
     *
     * Return the length of the given string.
     *
     * @param string $value
     * @return int
     */
    function str_length($value)
    {
        return Str::length($value);
    }
}

if (!function_exists('str_limit')) {
    /**
     * [Unicode Friendly]
     *
     * Limit the number of characters in a string.
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        return Str::limit($value, $limit, $end);
    }
}

if (!function_exists('str_lower')) {
    /**
     * [Unicode Friendly]
     *
     * Convert the given string to lower-case.
     *
     * @param string $value
     * @return string
     */
    function str_lower($value)
    {
        return Str::lower($value);
    }
}

if (!function_exists('str_limit_words')) {
    /**
     * [Unicode Friendly]
     *
     * Limit the number of words in a string.
     *
     * @param string $value
     * @param int $words
     * @param string $end
     * @return string
     */
    function str_limit_words($value, $words = 100, $end = '...')
    {
        return Str::words($value, $words, $end);
    }
}

if (!function_exists('str_plural')) {
    /**
     * Get the plural form of an English word.
     *
     * @param string $value
     * @param int $count
     * @return string
     */
    function str_plural($value, $count = 2)
    {
        return Str::plural($value, $count);
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param int $length
     * @return string
     */
    function str_random($length = 16)
    {
        return Str::random($length);
    }
}

if (!function_exists('str_random_quick')) {
    /**
     * Generate a "random" alpha-numeric string. Should not be considered sufficient for cryptography, etc.
     *
     * @param int $length
     * @return string
     */
    function str_random_quick($length = 16)
    {
        return Str::quickRandom($length);
    }
}

if (!function_exists('str_replace_first')) {
    /**
     * [Unicode Friendly]
     *
     * Replace the first occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    function str_replace_first($search, $replace, $subject)
    {
        return Str::replaceFirst($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_last')) {
    /**
     * [Unicode Friendly]
     *
     * Replace the last occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    function str_replace_last($search, $replace, $subject)
    {
        return Str::replaceLast($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_array')) {
    /**
     * [Unicode Friendly]
     *
     * Replace a given value in the string sequentially with an array.
     *
     * @param string $search
     * @param array $replace
     * @param string $subject
     * @return string
     */
    function str_replace_array($search, $replace, $subject)
    {
        return Str::replaceArray($search, $replace, $subject);
    }
}

if (!function_exists('str_upper')) {
    /**
     * [Unicode Friendly]
     *
     * Convert the given string to upper-case.
     *
     * @param string $value
     * @return string
     */
    function str_upper($value)
    {
        return Str::upper($value);
    }
}

if (!function_exists('kebab_case')) {
    /**
     * [Unicode Friendly]
     *
     * Convert a string to kebab case.
     *
     * @param string $value
     * @return string
     */
    function kebab_case($value)
    {
        return Str::kebab($value);
    }
}

if (!function_exists('title_case')) {
    /**
     * [Unicode Friendly]
     *
     * Convert the given string to title case.
     *
     * @param string $value
     * @return string
     */
    function title_case($value)
    {
        return Str::title($value);
    }
}

if (!function_exists('str_singular')) {
    /**
     * Get the singular form of an English word.
     *
     * @param string $value
     * @return string
     */
    function str_singular($value)
    {
        return Str::singular($value);
    }
}

if (!function_exists('str_slug')) {
    /**
     * [Unicode Friendly]
     *
     * Generate a URL friendly "slug" from a given string.
     *
     * @param string $title
     * @param string $separator
     * @return string
     */
    function str_slug($title, $separator = '-')
    {
        return Str::slug($title, $separator);
    }
}

if (!function_exists('snake_case')) {
    /**
     * [Unicode Friendly]
     *
     * Convert a string to snake case.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    function snake_case($value, $delimiter = '_')
    {
        return Str::snake($value, $delimiter);
    }
}

if (!function_exists('studly_case')) {
    /**
     * [Unicode Friendly]
     *
     * Convert a value to studly caps case.
     *
     * @param string $value
     * @return string
     */
    function studly_case($value)
    {
        return Str::studly($value);
    }
}

if (!function_exists('str_substring')) {
    /**
     * [Unicode Friendly]
     *
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return string
     */
    function str_substring($string, $start, $length = null)
    {
        return Str::substr($string, $start, $length);
    }
}

if (!function_exists('str_ucfirst')) {
    /**
     * [Unicode Friendly]
     *
     * Make a string's first character uppercase.
     *
     * @param string $string
     * @return string
     */
    function str_ucfirst($string)
    {
        return Str::ucfirst($string);
    }
}

if (!function_exists('str_find')) {
    /**
     * [Unicode Friendly]
     *
     * Finds the position of a substring in the given string. Returns false if not found.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return int|bool
     */
    function str_find($haystack, $needles)
    {
        return Str::find($haystack, $needles);
    }
}

if (!function_exists('str_before')) {
    /**
     * [Unicode Friendly]
     *
     * Get the portion of a string before a given value.
     *
     * @param string $subject
     * @param string $search
     * @return string
     */
    function str_before($subject, $search)
    {
        return Str::before($subject, $search);
    }
}

if (!function_exists('str_after')) {
    /**
     * [Unicode Friendly]
     *
     * Return the remainder of a string after a given value.
     *
     * @param string $subject
     * @param string $search
     * @return string
     */
    function str_after($subject, $search)
    {
        return Str::after($subject, $search);
    }
}
