<?php

namespace Groundwork\Injector;

interface Injection
{
    /**
     * Allows the class to be instantiated using dependency injection.
     *
     * @param mixed $param
     *
     * @return static
     */
    public static function __inject($param) : self;
}