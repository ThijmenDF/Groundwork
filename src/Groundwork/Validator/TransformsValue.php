<?php

namespace Groundwork\Validator;

interface TransformsValue
{
    /**
     * Transforms the given value to another type.
     *
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     */
    public function transform($value, array $params = []);
}