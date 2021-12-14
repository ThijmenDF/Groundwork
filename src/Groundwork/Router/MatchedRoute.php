<?php

namespace Groundwork\Router;

use Exception;
use Groundwork\Injector\Injector;
use Groundwork\Middleware\MiddlewareRunner;
use Groundwork\Request\Request;
use Groundwork\Utils\Table;

class MatchedRoute
{
    /** @var object The class instance */
    private object $class;

    /** @var string The method to be called */
    private string $method;

    /** @var array An associative array with the parameters  */
    private array $params;

    /** @var Table A table of middleware names to execute for this request. */
    private Table $middleware;

    public function __construct(object $class, string $method, array $params, array $middleware = [])
    {
        $this->class      = $class;
        $this->method     = $method;
        $this->params     = $params;
        $this->middleware = table($middleware);
    }

    /**
     * Attempts to call the method and provide dependency injection through the Injector class.
     *
     * @return mixed The result from the call or the exception thrown.
     * @throws Exception
     */
    public function call()
    {
        // Before we start calling the final method, call all middleware, in order, first.
        return $this->callMiddleware(request());
    }

    /**
     * Calls the next middleware in the stack.
     *
     * @param Request $request
     *
     * @return mixed|null
     */
    protected function callMiddleware(Request $request)
    {
        $runner = new MiddlewareRunner($this->middleware);

        return $runner->call($request, fn() => $this->callHandler());
    }

    /**
     * Calls the final handler and returns it response.
     */
    protected function callHandler()
    {
        $injector = new Injector($this->class);

        return $injector->provide($this->method, $this->params);
    }
}