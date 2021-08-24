<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class PresentRule extends Rule
{
    /**
     * Matches if the given rule is not empty.
     */
    public function passes($value, array $params = []): bool
    {
        return !empty($value);
    }
}