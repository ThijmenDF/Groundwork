<?php

namespace Groundwork\Validator\Rules;

use Countable;
use Groundwork\Validator\Rule;
use Groundwork\Validator\TransformsValue;

class RequiredRule extends Rule
{
    /**
     * Matches if the given rule is not null, empty, has no count or has no temporary upload file path.
     */
    public function passes($value, array $params = []): bool
    {
        if (isset($value['tmp_name'])) { // files must have a temp name (it's stored in the temporary folder)
            return !empty($value['tmp_name']);
        }

        if (is_numeric($value)) {
            return true;
        }

        if ($value instanceof Countable) { // Countable objects should have items
            return !!count($value);
        }

        return !empty($value); // it's not null (it exists) and not empty.
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This value is required.";
    }
}