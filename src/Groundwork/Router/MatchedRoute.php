<?php

namespace Groundwork\Router;

use Exception;
use Groundwork\Injector\Injector;
use Groundwork\Middleware\MiddlewareRunner;
use Groundwork\Request\Request;
use Groundwork\Utils\Table;

/**
 * An instance of a matched route. Can be 'called'
 */
class MatchedRoute
{
    /** @var string The class name(space) */
    private string $class;

    /** @var string The method to be called */
    private string $method;

    /** @var array An associative array with the parameters  */
    private array $params;

    /** @var Table A table of middleware names to execute for this request. */
    private Table $middleware;

    /** @var string|null The name of the route */
    private ?string $name;

    public function __construct(array $match)
    {
        $this->class      = $match['target'][0];
        $this->method     = $match['target'][1] ?? 'index';
        $this->params     = $match['params'];
        $this->name       = $match['name'];
        $this->middleware = table($match['target'][2] ?? []);
    }

    /**
     * Attempts to call the method and provide dependency injection through the Injector class.
     *
     * @return mixed The result from the call or the exception thrown.
     * @throws Exception
     */
    public function call()
    {
        // Before we start calling the final method, call all middleware, in order.
        return $this->callMiddleware(request());
    }

    /**
     * Returns the route's name, if any was set.
     *
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * Returns the 'controller' class.
     *
     * @return string
     */
    public function getClass() : string
    {
        return $this->class;
    }

    /**
     * Returns the 'controller' method.
     *
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * Returns the params from the matched URL.
     *
     * @return array
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * Returns a readonly version of the middleware table.
     *
     * @return Table
     */
    public function getMiddleware() : Table
    {
        return $this->middleware->clone();
    }

    /**
     * Calls the next middleware in the stack. Returns the response from either a middleware, or the final call handler.
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

        // Builds up the class instance.
        $injector->construct();

        // Call the desired method on the, now instanced, class.
        return $injector->provide($this->method, $this->params);
    }
}