<?php

namespace Groundwork\Router;

class RouteDefinition {

    public string $method;

    public string $url;

    public array $handler;

    public ?string $name = null;

    public array $middleware;

    public function __construct(string $method, string $url, array $handler, array $middleware = [])
    {
        $this->method = $method;

        $this->url = $url;

        $this->handler = $handler;

        $this->middleware = $middleware;
    }

    public function name(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    public function middleware(string $name) : self
    {
        $this->middleware[] = $name;

        return $this;
    }

}