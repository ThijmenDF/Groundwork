<?php

namespace Groundwork\Validator\Rules;

use Groundwork\Validator\Rule;

class ImageRule extends Rule
{
    protected array $types = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp'];

    /**
     * Passes if the given input is a valid uploaded file.
     */
    public function passes($value, array $params = []): bool
    {
        if(empty($value) || !isset($value['name'], $value['type'], $value['size'])) {
            return false;
        }

        $imageFileType = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));

        if (getimagesize($value['tmp_name']) && in_array($imageFileType, $this->types)) {
            return true;
        }

        return false;
    }

    public function getErrorMessage($value, array $params = []) : string
    {
        return "This file must be an image of any of these types: " . implode(', ', $this->types);
    }
}