<?php

namespace Groundwork\Container;

use Groundwork\Exceptions\Container\NotFoundException;
use Groundwork\Injector\Injector;
use Groundwork\Utils\Table;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    protected Table $instances;

    public function __construct()
    {
        $this->instances = table();
    }

    /**
     * Registers a new identifier and whatever is bound to it.
     *
     * If the instance is an object, it stores the instance as-is.
     *
     * If the instance is a string (class path), it will construct the class whenever it's first requested, and return
     * the instanced class afterwards.
     *
     * @param string $id
     * @param object|string $instance
     *
     * @return void
     */
    public function register(string $id, $instance)
    {
        $this->instances->set($id, $instance);
    }

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new NotFoundException('Container ' . $id . ' has not been registered');
        }

        $instance = $this->instances->get($id);

        if (gettype($instance) === 'object') {
            // Return the instance, as it's already a class.
            return $instance;
        }

        // Start a new injector instance.
        $injector = new Injector($instance);

        // Provide the __construct method.
        $instance = $injector->construct();

        // Save the new instance to the stack.
        $this->register($id, $instance);

        // Return the fresh instance.
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function has(string $id) : bool
    {
        return $this->instances->has($id);
    }
}