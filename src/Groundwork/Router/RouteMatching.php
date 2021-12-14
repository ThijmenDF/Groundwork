<?php

namespace Groundwork\Router;

use Closure;

/**
 * Adds the ability to map the common CRUD operations as HTTP verbs.
 */
trait RouteMatching {

    /** @var array A list of all known routes  */
    protected array $routes = [];

    /** @var array A list of currently active middleware */
    protected array $currentMiddleware = [];

    /**
     * Define a route that uses the GET method.
     *
     * @param string $url
     * @param array  $handler
     *
     * @return RouteDefinition
     */
    public function get(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('GET', $url, $handler);
    }

    /**
     * Define a route that uses the POST method.
     *
     * @param string $url
     * @param array  $handler
     *
     * @return RouteDefinition
     */
    public function post(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('POST', $url, $handler);
    }

    /**
     * Define a route that uses the PUT method.
     *
     * @param string $url
     * @param array  $handler
     *
     * @return RouteDefinition
     */
    public function put(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('PUT', $url, $handler);
    }

    /**
     * Define a route that uses the DELETE method.
     *
     * @param string $url
     * @param array  $handler
     *
     * @return RouteDefinition
     */
    public function delete(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('DELETE', $url, $handler);
    }

    /**
     * Define a route that uses the PATCH method.
     *
     * @param string $url
     * @param array  $handler
     *
     * @return RouteDefinition
     */
    public function patch(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('PATCH', $url, $handler);
    }

    /**
     * Define a route that uses an array of different methods.
     *
     * @param array  $methods
     * @param string $url
     * @param array  $handler
     *
     * @return RouteDefinition
     */
    public function match(array $methods, string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition(implode('|', $methods), $url, $handler);
    }

    /**
     * Apply one or more middleware to the given closure.
     *
     * @param string|string[] $names
     * @param Closure         $handler
     *
     * @return void
     */
    public function middleware($names, Closure $handler)
    {
        if (! is_array($names)) {
            $names = [$names];
        }

        $previousState = $this->currentMiddleware;

        $this->currentMiddleware = array_merge($this->currentMiddleware, $names);

        $handler($this);

        $this->currentMiddleware = $previousState;
    }

    /**
     * Create a new RouteDefinition and save it to the route stash.
     *
     * @param string $method
     * @param string $url
     * @param array  $handler
     *
     * @return RouteDefinition
     */
    protected function createDefinition(string $method, string $url, array $handler) : RouteDefinition
    {
        $definition = new RouteDefinition($method, $url, $handler, $this->currentMiddleware);

        $this->routes[] = $definition;

        return $definition;
    }
}