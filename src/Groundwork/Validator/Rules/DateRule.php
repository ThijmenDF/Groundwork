<?php

namespace Groundwork\Validator\Rules;

use Carbon\Carbon;
use Groundwork\Validator\TransformsValue;
use Groundwork\Validator\Rule;

class DateRule extends Rule implements TransformsValue
{
    /**
     * Passes if the given input can be parsed into a datetime field
     */
    public function passes($value, array $params = []): bool
    {
        return !!strtotime($value);
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "'$value' is not a valid date.";
    }

    public function transform($value, array $params = [])
    {
        return Carbon::createFromTimestamp(strtotime($value));
    }
}