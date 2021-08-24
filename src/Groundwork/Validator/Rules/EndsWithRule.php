<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Utils\Str;
use Groundwork\Validator\Rule;

class EndsWithRule extends Rule
{
    /**
     * Passes if the given value matches a valid e-mail
     */
    public function passes($value, array $params = []): bool
    {
        return Str::endsWith($value, $params);
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This must end with" . (count($params) > 1 ? " one of: " . implode(', ', $params) : ": " . $params[0]);
    }
}