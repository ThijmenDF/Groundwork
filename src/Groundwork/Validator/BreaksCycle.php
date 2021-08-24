<?php

namespace Groundwork\Validator;

interface BreaksCycle
{
    /**
     * If the result from this method is true, the rule check cycle will be discontinued and the value will be marked as 'valid'.
     *
     * @param       $value
     * @param array $params
     *
     * @return bool
     */
    public function breakIf($value, array $params = []) : bool;
}