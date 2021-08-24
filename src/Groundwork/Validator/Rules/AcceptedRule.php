<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;
use Groundwork\Validator\TransformsValue;

class AcceptedRule extends Rule implements TransformsValue
{
    /**
     * Passes if the given value matches an 'active' or 'checked' checkbox or something similar.
     */
    public function passes($value, array $params = []): bool
    {
        return in_array($value, ['yes', 'on', '1', 1, 'true', true], true);
    }

    public function transform($value, array $params = []) : bool
    {
        return true;
    }
}