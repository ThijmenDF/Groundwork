<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class MinRule extends Rule
{
    /**
     * Passes if the given input, as a numeric value, is greater (or equal) to a given number.
     *
     * If the given value isn't numeric, it'll need a specific string length.
     */
    public function passes($value, array $params = []): bool
    {
        if (!is_numeric($value)) {
            $value = strlen($value);
        }

        return $value >= $params[0];
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return is_numeric($value) ? "This cannot be less than " . $params[0] . "." : "The length cannot be less than " . $params[0];
    }
}