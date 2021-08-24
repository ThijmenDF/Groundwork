<?php

namespace Groundwork\Injector;

use Groundwork\Exceptions\ValidationFailedException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
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

        $parameters = table($method->getParameters());

        $parameters->each(function (ReflectionParameter $definition) use($params, $returned) {
            $key = $definition->getName();
            $class = $definition->getClass();

            // If the key also exists in the parameter list...
            if ($params->has($key)) {
                if ($definition->getType()->isBuiltin()) {
                    // a built-in type is automatically cast.
                    $returned->set($key, $params->get($key));
                }
                elseif ($class->implementsInterface(Injection::class)) {
                    // if the class implements the Injection interface, call that.
                    $returned->set($key, call_user_func($class->getName() . '::__inject', $params->get($key)));
                }
                else {
                    // default action: new instance of the class with the key given as its first and only parameter.
                    $name = $class->getName();
                    $returned->set($key, new $name($params->get($key)));
                }
                return;
            }

            // The key isn't given.
            if ($class->implementsInterface(Injection::class)) {
                $returned->set($key, call_user_func($class->getName() . '::__inject', null));
            }
            else {
                $returned->set($key, null);
            }
        });

        return $returned->all();
    }
}