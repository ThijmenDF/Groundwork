<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\BreaksCycle;
use Groundwork\Validator\Rule;
use Groundwork\Validator\TransformsValue;

class NullableRule extends Rule implements BreaksCycle, TransformsValue
{
    /**
     * Passes regardless of what the type is.
     */
    public function passes($value, array $params = []): bool
    {
        return true;
    }

    public function breakIf($value, array $params = []) : bool
    {
        return empty($value);
    }

    public function transform($value, array $params = [])
    {
        return empty($value) ? null : $value;
    }
}