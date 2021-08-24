<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class ArrayRule extends Rule
{
    /**
     * Passes if the given input is parsed as an array.
     */
    public function passes($value, array $params = []): bool
    {
        return is_array($value);
    }
}