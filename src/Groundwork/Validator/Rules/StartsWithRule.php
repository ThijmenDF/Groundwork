<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Utils\Str;
use Groundwork\Validator\Rule;

class StartsWithRule extends Rule
{
    /**
     * Matches if the given value starts with any of the list of strings
     */
    public function passes($value, array $params = []): bool
    {
        return Str::startsWith($value, $params);
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This must start with" . (count($params) > 1 ? " one of: " . implode(', ', $params) : ": " . $params[0]);
    }
}