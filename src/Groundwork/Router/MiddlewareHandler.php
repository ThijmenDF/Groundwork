<?php

namespace Groundwork\Router;

use Closure;
use Groundwork\Request\Request;

interface MiddlewareHandler {
    /**
     * Handles the request and returns the response. Calls `$next` in order to run the next middleware in the stack,
     * or to run the target class and method.
     * 
     * @param Request $request
     * @param Closure $next
     * 
     * @return mixed
     */
    public function handle(Request $request, Closure $next);
}
