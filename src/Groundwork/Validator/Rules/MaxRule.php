<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class MaxRule extends Rule
{
    /**
     * Passes if the given input, as a numeric value, is less or equal to than a given number.
     */
    public function passes($value, array $params = []): bool
    {
        return $value <= $params[0];
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This cannot be more than " . $params[0] . ".";
    }
}