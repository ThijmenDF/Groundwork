<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class EmailRule extends Rule
{
    /**
     * Passes if the given value matches a valid e-mail
     */
    public function passes($value, array $params = []): bool
    {
        return !!filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This is not a valid e-mail address.";
    }
}