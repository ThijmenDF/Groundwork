<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class FileRule extends Rule
{
    /**
     * Passes if the given input is a valid uploaded file.
     */
    public function passes($value, array $params = []): bool
    {
        return !empty($value) && 
               isset($value['name'], $value['type'], $value['size']) && 
               $value['size'] > 0 &&
               $value['error'] == 0;
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This file is invalid or there was an error uploading it.";
    }
}