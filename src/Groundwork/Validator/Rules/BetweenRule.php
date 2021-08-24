<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class BetweenRule extends Rule
{
    /**
     * Passes if the given input, as a numeric value, is between two numeric values.
     */
    public function passes($value, array $params = []): bool
    {
        return clamp($value, $params[0], $params[1]) === $value;
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This must be between " . $params[0]. " and " . $params[1] . ".";
    }
}