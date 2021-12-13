<?php

namespace Groundwork\Router;

use Exception;
use Groundwork\Injector\Injector;
use Groundwork\Request\Request;
use Groundwork\Utils\Str;
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

    protected function callMiddleware(Request $request, int $index = 0)
    {
        // If no more middleware exist, run the desired handler instead.
        if (! $this->middleware->has($index)) {
            return $this->callHandler();
        }

        $name = 'App\\Middleware\\' . Str::studly($this->middleware->get($index));

        // First instantiate the middleware using dependency injection.
        $injector = new Injector($name);
        $middleware = $injector->provide();

        // The 'next' function simply calls the next middleware in the stack. If the last middleware is reached, it runs the desired class and method instead.
        $next = function(Request $request) use ($index) {
            return $this->callMiddleware($request, $index + 1);
        };

        // Then call the middleware. Provide it with a 'next' method which it may call to trigger the next middleware.
        return $middleware->handle($request, $next);
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