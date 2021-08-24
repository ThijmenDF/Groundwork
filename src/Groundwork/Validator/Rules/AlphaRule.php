<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class AlphaRule extends Rule
{
    /**
     * Passes if the given input only consists of alphabetic characters.
     */
    public function passes($value, array $params = []): bool
    {
        return !!preg_match('/^[a-zA-Z\s]+$/', $value);
    }
}