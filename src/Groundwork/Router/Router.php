<?php

namespace Groundwork\Router;

use AltoRouter;
use Exception;
use Groundwork\Config\Config;
use Groundwork\Database\Model;
use Groundwork\Exceptions\Http\NotFoundException;
use Groundwork\Exceptions\RouterConfigurationException;
use Groundwork\Migration\Migrator;
use Groundwork\Traits\Singleton;
use Groundwork\Utils\Files\FileHandler;
use Groundwork\Utils\Files\FileInfo;

class Router {

    // Only one instance of the Router may exist at once.
    use Singleton;

    /** @var AltoRouter The external router instance */
    private AltoRouter $router;

    /** @var MatchedRoute|null The matched route */
    private MatchedRoute $match;

    // Set up the router.
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
     * Loads the routes defined in *.php within the 'routes' directory.
     */
    private function loadRoutes() : void
    {
        $router = $this->router;

        FileHandler::scan(root() . 'routes', '.php')
            ->each(function(FileInfo $file) use($router) {
                // Load the user-defined routes
                require $file->path();
            });
    }

    /**
     * Loads up the migration routes.
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
     * @return MatchedRoute
     * @throws NotFoundException
     * @throws RouterConfigurationException
     */
    public function matchRoutes() : MatchedRoute
    {
        // Match the routes
        $match = $this->router->match(null, request()->method());

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
                // Unknown route target. Other features such as inline functions are not supported (for now).
                throw new RouterConfigurationException;
            }

            // Attempt to make a new instance of the controller
            $controller = new $controllerName;

            $this->match = new MatchedRoute($controller, $method, $match['params']);

            // Return the controller instance, method and any parameters passed in the URL.
            return $this->match;
        }

        // No match, throw an NotFound exception, which will be shown as a nice looking exception page.
        throw new NotFoundException($_SERVER['REQUEST_URI'] ?? '/');
    }

    /**
     * Generates a new route URL.
     *
     * The name needs to be registered in order for this to function.
     *
     * @param string $name The route name
     * @param array  $data Any param data. This must be an associative array.
     *
     * @return string The generated route
     * @throws Exception if the route name cannot be found.
     */
    public function getRoute(string $name, array $data = []) : string
    {
        $data = table($data);

        $data->transform(function($item) {
            if ($item instanceof Model) {
                // Extract the models identifier
                return $item->getIdentifier();
            }
            return $item;
        });

        return $this->router->generate($name, $data->all());
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