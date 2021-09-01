<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class MaxRule extends Rule
{
    /**
     * Passes if the given input, as a numeric value, is less or equal to than a given number.
     *
     * If the given value is not numeric, it'll check its string length instead.
     */
    public function passes($value, array $params = []): bool
    {
        if (!is_numeric($value)) {
            $value = strlen($value);
        }

        return $value <= $params[0];
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return is_numeric($value) ? "This cannot be more than " . $params[0] . "." : "The length cannot be more than " . $params[0];
    }
}