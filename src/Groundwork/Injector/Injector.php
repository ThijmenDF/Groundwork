<?php

namespace Groundwork\Injector;

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
     *
     * @throws ReflectionException
     */
    public function __construct($class)
    {
        $this->class = $class;

        $this->reflection = new ReflectionClass($class);
    }

    /**
     * Calls the method with the parameters it requested. Parameters are filled through the $params param, or from the
     * __inject method if no parameter with such name exists.
     *
     * @param string $method The method to call
     * @param array  $params The params to give the method. These may get overwritten through dependency injection.
     *
     * @return false|mixed
     * @throws ReflectionException|ValidationFailedException
     */
    public function provide(string $method, array $params)
    {
        return call_user_func_array(
            [$this->class, $method],
            $this->inject($this->reflection->getMethod($method), $params)
        );
    }

    /**
     * Loops through the method's parameters. If any `$params` matches their key name, they'll be added or transformed
     * through the class' `__inject` method. Should `$params` not contain such a key, it'll be injected with the `__inject`
     * method. If none of those conditions are met, the value will be `null`.
     *
     * @param ReflectionMethod $method
     * @param array            $params
     *
     * @return array
     *
     * @todo improve functionality by allowing non-associative arrays to work.
     * @throws ValidationFailedException
     */
    public function inject(ReflectionMethod $method, array $params) : array
    {
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
        $class = new ReflectionClass($type->getName());

        if ($class->implementsInterface(Injection::class)) {
            // if the class implements the Injection interface, call that.
            return call_user_func($name . '::__inject', $value);
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

        for ($i = 0; $i < count($types); $i++) {
            return $this->getFirstType($types[$i]);
        }
    }
}
