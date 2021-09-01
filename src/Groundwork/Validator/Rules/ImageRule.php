<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Request\FileUpload;
use Groundwork\Validator\Rule;

class ImageRule extends Rule
{
    protected array $types = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp'];

    /**
     * Passes if the given input is a valid image.
     */
    public function passes($value, array $params = []): bool
    {
        if (!($value instanceof FileUpload) || $value->hasError()) {
            return false;
        }

        if (getimagesize($value->tmpName()) && in_array(strtolower($value->ext()), $this->types)) {
            return true;
        }

        return false;
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This file must be an image of any of these types: " . implode(', ', $this->types);
    }
}