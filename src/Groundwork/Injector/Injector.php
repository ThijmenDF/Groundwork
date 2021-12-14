<?php

namespace Groundwork\Injector;

use BadMethodCallException;
use Groundwork\Exceptions\ValidationFailedException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class Injector
{
    /** @var string|object The class that's going to be reflected */
    protected $class;

    /** @var ReflectionClass The class reflection */
    protected ReflectionClass $reflection;

    /**
     * Takes the given class (or class name) and makes a reflection of it. Opens up the `provide` method, which will
     * act as a dependency injector for the given method name. Only works for non-static methods.
     *
     * @param string|object $class
     */
    public function __construct($class)
    {
        $this->class = $class;

        try {
            $this->reflection = new ReflectionClass($class);
        } catch (ReflectionException $ex) {
            throw new \InvalidArgumentException('The given class could not be reflected', 0, $ex);
        }
    }

    /**
     * Calls the method with the parameters it requested. Parameters are filled through the $params param, or from the
     * __inject method if no parameter with such name exists.
     *
     * @param string|null $method The method to call
     * @param array       $params The params to give the method. These may get overwritten through dependency injection.
     *
     * @return mixed
     */
    public function provide(?string $method = null, array $params = [])
    {
        if (is_null($method)) {
            return new $this->class(
                ...$this->inject($this->reflection->getConstructor(), $params)
            );
        }
        
        if (method_exists($this->class, $method)) {
            try {
                return call_user_func_array(
                    [$this->class, $method],
                    $this->inject($this->reflection->getMethod($method), $params)
                );
            } catch (ReflectionException $ex) {
                return null;
            }
        }


        throw new BadMethodCallException('Method ' . $method . ' could not be found on ' . $this->class);
    }

    /**
     * Loops through the method's parameters. If any `$params` matches their key name, they'll be added or transformed
     * through the class' `__inject` method. Should `$params` not contain such a key, it'll be injected with the `__inject`
     * method. If none of those conditions are met, the value will be `null`.
     *
     * @param ReflectionMethod|null $method
     * @param array                 $params
     *
     * @return array
     *
     * @todo improve functionality by allowing non-associative arrays to work.
     */
    public function inject(?ReflectionMethod $method, array $params) : array
    {
        if (is_null($method)) {
            return $params;
        }
        
        $params = table($params);
        $returned = table();

        table($method->getParameters())
            ->each(function (ReflectionParameter $definition) use($params, $returned) {
                $key = $definition->getName();

                $returned->set($key, $this->handleProvisioning($definition, $params->get($key)));
            });

        return $returned->all();
    }

    /**
     * Processes each parameter of the requested method and its equally named value from the given array.
     *
     * @param ReflectionParameter $definition
     * @param mixed               $value
     *
     * @return mixed The result from the class or value
     */
    protected function handleProvisioning(ReflectionParameter $definition, $value)
    {
        $type = $this->getFirstType($definition->getType());

        if (is_null($type)) { // Null types can't be transformed.
            return $value;
        }

        $name = $type->getName();

        if ($type->isBuiltin()) { // Built-in types can be transformed by PHP itself.
            return $value;
        }
        
        // All other types *should* have a class
        try {
            $class = new ReflectionClass($type->getName());

            if ($class->implementsInterface(Injection::class)) {
                // if the class implements the Injection interface, call that.
                return call_user_func($name . '::__inject', $value);
            }
        } catch (ReflectionException $ex) {
            // do nothing...
        }

        // otherwise, make a new instance of the class with the value given as its first and only parameter.
        return new $name($value);
    }

    /**
     * Returns the first ReflectionNamedType it can find.
     * 
     * @param ReflectionNamedType|ReflectionUnionType|null $type
     * 
     * @return ReflectionNamedType|null
     */
    protected function getFirstType($type) : ?ReflectionNamedType
    {
        if ($type instanceof ReflectionNamedType || is_null($type)) {
            return $type;
        }

        $types = $type->getTypes();

        return $this->getFirstType($types[0]);
    }
}
