<?php

namespace Groundwork\Traits;

/**
 * This trait allows a class to have a single instance of it available anywhere in the code.
 * 
 * Because this is available anywhere in the codebase, it's considered a bad practice and 
 * should only be used when there really is no other way. (such as with database connections)
 * 
 * For it to be effective, the class' construct method should be private.
 * 
 * In order to get an instance, the class can be statically called with `::getInstance()`
 */
trait Singleton {
    
    /**
     * The instance stored for this class
     * 
     * @var self $instance
     */
    protected static $instance = null;

    /**
     * Returns the instance or creates a new one if it doesn't exist yet.
     * 
     * @return static
     */
    public static function getInstance() : self
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

}