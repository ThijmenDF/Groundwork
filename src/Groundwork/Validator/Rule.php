<?php

namespace Groundwork\Validator;

class Rule 
{

    /**
     * Returns whether a given value passes the rule.
     * 
     * @param mixed $value
     * @param array $params
     * 
     * @return bool
     */
    public function passes($value, array $params = []) : bool
    {
        return true;
    }

    /**
     * Returns a string containing a reason why the validation would fail.
     *
     * @param mixed  $value
     * @param array  $params
     *
     * @return string
     */
    public function getErrorMessage($value, array $params = []) : string
    {
        return 'This value is invalid.';
    }

}