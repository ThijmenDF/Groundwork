<?php

namespace Groundwork\Router;

class RouteDefinition {

    /** @var string  */
    public string $method;

    /** @var string  */
    public string $url;

    /** @var array  */
    public array $handler;

    /** @var string|null  */
    public ?string $name = null;

    /** @var array  */
    public array $middleware;

    public function __construct(string $method, string $url, array $handler, array $middleware = [])
    {
        $this->method = $method;

        $this->url = $url;

        $this->handler = $handler;

        $this->middleware = $middleware;
    }

    /**
     * Sets a name for the given route. This is used when generating a new route. All route names *must* be unique.
     *
     * @param string $name
     *
     * @return $this
     */
    public function name(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Apply a middleware specifically to this route. This method can be stacked on itself for multiple middleware.
     *
     * @param string $name
     *
     * @return $this
     */
    public function middleware(string $name) : self
    {
        $this->middleware[] = $name;

        return $this;
    }

}