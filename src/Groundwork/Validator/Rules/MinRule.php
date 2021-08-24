<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class MinRule extends Rule
{
    /**
     * Passes if the given input, as a numeric value, is greater (or equal) to a given number
     */
    public function passes($value, array $params = []): bool
    {
        return $value >= $params[0];
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This cannot be less than " . $params[0] . ".";
    }
}