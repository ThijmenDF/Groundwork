<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class DigitsBetweenRule extends Rule
{
    /**
     * Passes if the given input has an amount of digits between two given values.
     */
    public function passes($value, array $params = [10, 20]): bool
    {
        $len = strlen($value);

        return is_numeric($value) && $len >= (int) $params[0] && $len <= (int) $params[1];
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This must have between " . $params[0] . " and " . $params[1] . " digits.";
    }
}