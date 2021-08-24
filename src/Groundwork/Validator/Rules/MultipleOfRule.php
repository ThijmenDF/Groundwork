<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class MultipleOfRule extends Rule
{
    /**
     * Passes if the given input, as a numeric value, is a multiple of the given number.
     */
    public function passes($value, array $params = []): bool
    {
        return $value % $params[0] === 0;
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This needs to be a multiple of " . $params[0] . ". The closes values are " . floor($value / $params[0]) * $params[0] . " and " . ceil($value / $params[0]) * $params[0] . ".";
    }
}