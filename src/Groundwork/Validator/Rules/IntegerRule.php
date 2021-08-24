<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;
use Groundwork\Validator\TransformsValue;

class IntegerRule extends Rule implements TransformsValue
{
    /**
     * Matches if the given value is an integer
     */
    public function passes($value, array $params = []): bool
    {
        return is_numeric($value) && is_int((int) $value);
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This needs to be a whole number.";
    }

    public function transform($value, array $params = []) : int
    {
        return (int) $value;
    }
}