<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class BetweenRule extends Rule
{
    /**
     * Passes if the given input, as a numeric value, is between two numeric values.
     *
     * If the value isn't numeric, it'll check its string length instead.
     */
    public function passes($value, array $params = []): bool
    {
        if (!is_numeric($value)) {
            $value = strlen($value);
        }

        return clamp($value, $params[0], $params[1]) === $value;
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return (is_numeric($value) ? "This number" : "The length" ) . " must be between " . $params[0]. " and " . $params[1] . ".";
    }
}