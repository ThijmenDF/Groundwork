<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class DigitsRule extends Rule
{
    /**
     * Passes if the given input has a exact amount of digits.
     */
    public function passes($value, array $params = [10]): bool
    {
        return is_numeric($value) && strlen($value) === (int) $params[0];
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This must have exactly " . $params[0] . " digits.";
    }
}