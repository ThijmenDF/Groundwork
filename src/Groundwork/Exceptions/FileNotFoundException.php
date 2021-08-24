<?php

namespace Groundwork\Exceptions;

use Exception;

class FileNotFoundException extends Exception
{
    public function __construct(string $filename)
    {
        parent::__construct("`$filename` could not be found.", 404);
    }
}