<?php

namespace Groundwork\Router;

use AltoRouter;
use Exception;
use Groundwork\Config\Config;
use Groundwork\Exceptions\Http\NotFoundException;
use Groundwork\Exceptions\RouterConfigurationException;
use Groundwork\Migration\Migrator;
use Groundwork\Traits\Singleton;

class Router {

    // Only one instance of the Router may exist at once.
    use Singleton;

    // Set up the router.
    private AltoRouter $router;

    /** @var array The matched route. */
    private array $match;
    
    private function __construct()
    {
        // Set up a new Router instance
        $this->router = new AltoRouter();

        if (Config::get('ENABLE_MIGRATOR')) {
            $this->loadVendorRoutes();
        }

        // Load the routes
        $this->loadRoutes();
    }
    
    /**
     * Loads up routes defined in routes.php
     */
    private function loadRoutes() : void
    {
        $router = $this->router;
        // Load the user-defined routes
        require root() . "/routes/routes.php";
    }

    /**
     * Loads up some vendor routes.
     *
     */
    private function loadVendorRoutes() : void
    {
        try {
            // Migrate
            $this->router->map('GET', '/migrations/migrate', [Migrator::class, 'migrate'], 'migrator-migrate');

            // Rollback
            $this->router->map('GET', '/migrations/rollback/[i:steps]?', [Migrator::class, 'rollback'], 'migrator-rollback');

            // Seed the DB
            $this->router->map('GET', '/migrations/seed', [Migrator::class, 'seed'], 'migrator-seed');

            // reset the DB
            $this->router->map('GET', '/migrations/purge', [Migrator::class, 'queryPurge'], 'migrator-purge-query');
            $this->router->map('GET', '/migrations/purge/confirm', [Migrator::class, 'purge'], 'migrator-purge');
        } catch (Exception $exception) {
            error_log('Exception loading vendor routes! ' . $exception->getMessage());
        }
    }

    /**
     * Runs the route matching and returns the controller, method and params
     *
     * @return array
     * @throws NotFoundException
     * @throws RouterConfigurationException
     */
    public function matchRoutes() : array
    {
        // Match the routes
        $match = $this->router->match();

        // A route was matched
        if ($match) {
            // See what the user wanted to run
            $method = 'index';
            if (is_array($match['target'])) {
                $controllerName = $match['target'][0];
                $method = $match['target'][1];
            } elseif (is_string($match['target'])) {
                $controllerName = $match['target'];
            } else {
                // Unknown route target
                throw new RouterConfigurationException;
            }
            
            // Attempt to make a new instance of the controller
            $controller = new $controllerName;

            $this->match = $match;

            // Call the method on that controller and pass the route parameters to it.
            return [$controller, $method, $match['params']];

        }

        // No match, throw an notFound exception
        throw new NotFoundException($_SERVER['REQUEST_URI'] ?? '/');
    }

    /**
     * Generates a new route URL.
     *
     * The name needs to be registered in order for this to function.
     *
     * @param string $name The route name
     * @param array  $data Any param data
     *
     * @return string The generated route
     * @throws Exception if the route name cannot be found.
     */
    public function getRoute(string $name, array $data = []) : string
    {
        return $this->router->generate($name, $data);
    }

    /**
     * Attempts to get the current route name.
     *
     * @return string|null
     */
    public function getRouteName() : ?string
    {
        return $this->match['name'] ?? null;
    }

    /**
     * Attempts to get the current route name.
     *
     * @return array|null
     */
    public function getParams() : ?array
    {
        return $this->match['params'] ?? null;
    }

}