<?php

namespace Groundwork\Router;

use Closure;

/**
 * Adds the ability to map the common CRUD operations as HTTP verbs.
 */
trait RouteMatching {

    protected array $routes = [];

    protected array $currentMiddleware = [];


    public function get(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('GET', $url, $handler);
    }

    public function post(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('POST', $url, $handler);
    }

    public function put(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('PUT', $url, $handler);
    }

    public function delete(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('DELETE', $url, $handler);
    }

    public function patch(string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition('PATCH', $url, $handler);
    }

    public function match(array $methods, string $url, array $handler) : RouteDefinition
    {
        return $this->createDefinition(implode('|', $methods), $url, $handler);
    }

    public function middleware($names, Closure $handler)
    {
        if (! is_array($names)) {
            $names = [$names];
        }

        $previousState = $this->currentMiddleware;

        $this->currentMiddleware = array_merge($this->currentMiddleware, $names);

        $handler();

        $this->currentMiddleware = $previousState;
    }

    protected function createDefinition(string $method, string $url, array $handler) : RouteDefinition
    {
        $definition = new RouteDefinition($method, $url, $handler, $this->currentMiddleware);

        $this->routes[] = $definition;

        return $definition;
    }
}