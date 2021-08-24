<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class StringRule extends Rule
{
    /**
     * Matches if the given value is a string
     */
    public function passes($value, array $params = []): bool
    {
        return is_string($value);
    }
}