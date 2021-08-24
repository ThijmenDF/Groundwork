<?php

namespace Groundwork\Utils;

/**
 * Contains a bunch of useful string methods, mostly inspired by Laravels Str class.
 */
class Str {

    /**
     * Returns true if any of the needles match the end of the haystack.
     * 
     * @param string $haystack The string to test against
     * @param string|array $needles The string(s) to test with
     * 
     * @return bool Whether any of the needles match the end of the haystack
     */
    public static function endsWith(string $haystack, $needles) : bool
    {
        if (is_string($needles)) {
            $needles = [$needles];
        }

        $len = strlen($haystack);

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) === $len - strlen($needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if any of the needles match the start of the haystack.
     * 
     * @param string $haystack The string to test against
     * @param string|array $needles The string(s) to test with
     * 
     * @return bool Whether any of the needles match the start of the haystack
     */
    public static function startsWith(string $haystack, $needles) : bool
    {
        if (is_string($needles)) {
            $needles = [$needles];
        }

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns true if any of the needles are contained in the haystack.
     * 
     * @param string $haystack The string to test against
     * @param string|array $needles The string(s) to test with
     * 
     * @return bool Whether any of the needles are contained the haystack
     */
    public static function contains(string $haystack, $needles) : bool
    {
        if (is_string($needles)) {
            $needles = [$needles];
        }

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if all the needles are contained in the haystack.
     * 
     * @param string $haystack The string to test against
     * @param string|array $needles The string(s) to test with
     * 
     * @return bool Whether all the needles are contained the haystack
     */
    public static function containsAll(string $haystack, $needles) : bool
    {
        if (is_string($needles)) {
            $needles = [$needles];
        }

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Capitalizes the first letter of the string.
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function ucfirst(string $string) : string
    {
        return ucfirst($string);
    }

    /**
     * Transforms all letters of the string to uppercase.
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function upper(string $string) : string
    {
        return strtoupper($string);
    }

    /**
     * Transforms all letters of the string to lowercase.
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function lower(string $string) : string
    {
        return strtolower($string);
    }

    /**
     * Capitalizes each word in the string. e.g. 'this is a test' to 'This Is A Test'
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function title(string $string) : string
    {
        return ucwords($string);
    }

    /**
     * Adds `$with` to the front of `$string` if it doesn't start with `$with` already.
     * 
     * @param string $string The main string to return.
     * @param string $with   The string to prepend to the main string if it doesn't already do so.
     * 
     * @return string
     */
    public static function start(string $string, string $with) : string
    {
        if (self::startsWith($string, $with)) {
            return $string;
        }
        return $with . $string;
    }

    /**
     * Converts the string into slug case which is safe for use in URLs.
     *
     * @param string $string
     * @param string $separator
     *
     * @return string
     */
    public static function slug(string $string, string $separator = '-') : string
    {
        // Replace @ with 'at'
        $string = str_replace('@', $separator.'at'.$separator, $string);

        // transform to lowercase
        $string = self::lower($string);
        
        // Remove all characters that aren't the separator, letters, numbers or white-spaces.
        $string = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', $string);

        // Replace all separator and white-spaces with a single separator
        $string = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $string);

        // remove trailing and leading separators
        return trim($string, $separator);
    }

    /**
     * Converts the string to Studly Caps case. `StudlyCase`
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function studly(string $string) : string
    {
        $string = ucwords(str_replace(['-','_'], ' ', $string));

        return str_replace(' ', '', $string);
    }


    /**
     * Converts a string to kebab case. `kebab-case`
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function kebab(string $string) : string
    {
        return self::snake($string, '-');
    }

    /**
     * Converts a string to snake case. `snake_case`
     * 
     * @param string $string
     * @param string $separator
     * 
     * @return string
     */
    public static function snake(string $string, string $separator = '_') : string
    {
        // If the string doesn't entirely consist out of lower case letters
        if (! ctype_lower($string)) {

            // Capitalize the first letters of the words and replace the white-spaces with blanks
            $string = preg_replace('/\s+/u', '', ucwords($string));

            // Add the separator before each uppercase letter
            $string = self::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $separator, $string));
        }

        return $string;
    }

    /**
     * Converts a value to camel case. `camelCase`
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function camel(string $string) : string
    {
        return lcfirst(self::studly($string));
    }
}