<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class RegexRule extends Rule
{
    /**
     * Matches if the given value matches a given regex pattern.
     */
    public function passes($value, array $params = []): bool
    {
        return !!preg_match($params[0], $value);
    }
}