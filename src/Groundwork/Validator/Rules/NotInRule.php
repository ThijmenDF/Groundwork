<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class NotInRule extends Rule
{
    /**
     * Passes if the given input is not in the given list of options.
     */
    public function passes($value, array $params = []): bool
    {
        return !in_array($value, $params);
    }
}