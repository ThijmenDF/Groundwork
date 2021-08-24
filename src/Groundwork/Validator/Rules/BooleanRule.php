<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;
use Groundwork\Validator\TransformsValue;

class BooleanRule extends Rule implements TransformsValue
{
    /**
     * Passes if the given value matches boolean-like values (true, false, 1, 0 etc)
     */
    public function passes($value, array $params = []): bool
    {
        return in_array($value, ['true', 'false', 1, 0, '1', '0', true, false], true);
    }

    public function transform($value, array $params = []) : bool
    {
        if (is_bool($value)) {return $value;}

        if (is_numeric($value)) { return $value != 0; }

        return in_array($value, ['true'], true);
    }
}