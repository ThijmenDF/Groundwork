<?php

namespace Groundwork\Extensions;

use Groundwork\Injector\Injector;

abstract class ExtensionHandler
{
    /**
     * Attempts to load the extension by name. Doesn't do anything if the class doesn't exist.
     *
     * Extensions are loaded from the `App\Extensions` namespace.
     *
     * @param string $name   The extension class to load.
     * @param object $object The class to pass to the build method.
     */
    public static function loadExtension(string $name, object $object)
    {
        $className = "App\\Extensions\\$name";

        if (class_exists($className)) {
            $injector = new Injector($className);

            /** @var Extension $extension */
            $extension = $injector->construct();

            $extension->build($object);
        }
    }
}