<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Request\FileUpload;
use Groundwork\Validator\Rule;

class FileRule extends Rule
{
    /**
     * Passes if the given input is a validly uploaded file.
     */
    public function passes($value, array $params = []): bool
    {
        if (!($value instanceof FileUpload)) {
            return false;
        }

        return !$value->hasError();
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This file is invalid or there was an error uploading it.";
    }
}