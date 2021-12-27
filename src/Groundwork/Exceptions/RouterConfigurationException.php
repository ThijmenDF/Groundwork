<?php

namespace Groundwork\Exceptions;

use Groundwork\Exceptions\Http\InternalServerErrorException;

class RouterConfigurationException extends InternalServerErrorException
{
    public function __construct(string $message = null)
    {
        parent::__construct($message ?? 'Incorrect router configuration');
    }
}