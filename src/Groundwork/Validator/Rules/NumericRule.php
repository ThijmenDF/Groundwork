<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class NumericRule extends Rule
{
    /**
     * Passes if the given value is a numeric value. (int, float, decimal etc.)
     */
    public function passes($value, array $params = []): bool
    {
        return is_numeric($value);
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This needs to be a number.";
    }
}