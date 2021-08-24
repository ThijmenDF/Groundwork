<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;
use Groundwork\Validator\TransformsValue;

class JsonRule extends Rule implements TransformsValue
{
    /**
     * Passes if the given value is a valid JSON string.
     */
    public function passes($value, array $params = []): bool
    {
        return !is_null(@json_decode($value));
    }

    public function transform($value, array $params = [])
    {
        return json_decode($value);
    }
}