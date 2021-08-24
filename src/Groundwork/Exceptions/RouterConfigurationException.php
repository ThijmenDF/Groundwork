<?php

namespace Groundwork\Exceptions;

use Groundwork\Exceptions\Http\InternalServerErrorException;

class RouterConfigurationException extends InternalServerErrorException
{
    public function __construct()
    {
        parent::__construct('Incorrect router configuration');
    }
}