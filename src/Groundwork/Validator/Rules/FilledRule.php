<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class FilledRule extends Rule
{
    /**
     * Passes if the given value matches an 'active' or 'checked' checkbox or something similar.
     */
    public function passes($value, array $params = []): bool
    {
        return !empty($value);
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This cannot be empty";
    }
}