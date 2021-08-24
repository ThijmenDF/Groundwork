<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class InRule extends Rule
{
    /**
     * Passes if the given input is within the given params list.
     */
    public function passes($value, array $params = []): bool
    {
        return in_array($value, $params);
    }
}