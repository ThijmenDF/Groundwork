<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class LengthRule extends Rule
{
    /**
     * Passes if the given input has an exact amount of characters.
     */
    public function passes($value, array $params = [10]): bool
    {
        return strlen($value) === (int) $params[0];
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This must have a length of exactly " . $params[0] . " characters.";
    }
}