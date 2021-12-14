<?php

namespace Groundwork\Middleware;

use Closure;
use Groundwork\Injector\Injector;
use Groundwork\Request\Request;
use Groundwork\Utils\Str;
use Groundwork\Utils\Table;

class MiddlewareRunner
{
    protected Table $middleware;

    public function __construct(Table $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * Runs the given middleware in order and finally runs the passed handler.
     *
     * @param Request $request The generic Request object to pass into each middleware.
     * @param Closure $handler The handler for the target action, after all middleware have run.
     *
     * @return mixed The output of each middleware and handler.
     */
    public function call(Request $request, Closure $handler)
    {
        // If no more middleware exist, run the desired handler instead.
        if ($this->middleware->isEmpty()) {
            return $handler();
        }

        $name = 'App\\Middleware\\' . Str::studly($this->middleware->shift());

        // First instantiate the middleware using dependency injection.
        $injector = new Injector($name);

        /** @var MiddlewareHandler $middleware */
        $middleware = $injector->provide();

        // The 'next' function simply calls the next middleware in the stack. If the last middleware is reached, it runs the desired class and method instead.
        $next = function(Request $request) use ($handler) {
            return $this->call($request, $handler);
        };

        // Then call the middleware. Provide it with a 'next' method which it may call to trigger the next middleware.
        return $middleware->handle($request, $next);
    }
}