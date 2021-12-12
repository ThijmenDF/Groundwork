<?php

namespace Groundwork\Router;

use Exception;
use Groundwork\Injector\Injector;

class MatchedRoute
{
    /** @var object The class instance */
    private object $class;
    /** @var string The method to be called */
    private string $method;
    /** @var array An associative array with the parameters  */
    private array $params;

    public function __construct(object $class, string $method, array $params)
    {
        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * Attempts to call the method and provide dependency injection through the Injector class.
     *
     * @return mixed The result from the call or the exception thrown.
     * @throws Exception
     */
    public function call()
    {
        $injector = new Injector($this->class);

        return $injector->provide($this->method, $this->params);
    }
}