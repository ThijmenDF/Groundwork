<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class ProhibitedRule extends Rule
{
    /**
     * Matches if the given rule is empty or null.
     */
    public function passes($value, array $params = []): bool
    {
        return empty($value);
    }
}