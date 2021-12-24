<?php

namespace Groundwork\Router;

use AltoRouter;
use Exception;
use Groundwork\Config\Config;
use Groundwork\Database\Model;
use Groundwork\Exceptions\Http\NotFoundException;
use Groundwork\Exceptions\RouterConfigurationException;
use Groundwork\Injector\Injector;
use Groundwork\Migration\Migrator;
use Groundwork\Traits\Singleton;
use Groundwork\Utils\Files\FileHandler;
use Groundwork\Utils\Files\FileInfo;

class Router {

    use RouteMatching;

    /** @var AltoRouter The external router instance */
    private AltoRouter $router;

    /** @var MatchedRoute|null The matched route */
    private MatchedRoute $match;

    // Set up the router.
    public function __construct()
    {
        // Set up a new Router instance
        $this->router = new AltoRouter();

        if (Config::get('ENABLE_MIGRATOR')) {
            $this->loadVendorRoutes();
        }

        // Load the routes
        $this->loadRoutes();

        $this->setupRoutes();
    }
    
    /**
     * Loads the routes defined in *.php within the 'routes' directory.
     */
    private function loadRoutes() : void
    {
        FileHandler::scan(root() . 'routes', '.php')
            ->each(function(FileInfo $file) {
                $router = $this;
                // Load the user-defined routes
                require $file->path();
            });

    }

    private function setupRoutes() : void
    {
        /** @var RouteDefinition $route */
        foreach ($this->routes as $route) {
            $handler = $route->handler;
            $handler[2] = $route->middleware;

            $this->router->map($route->method, $route->url, $handler, $route->name);
        }
    }

    /**
     * Loads up the migration routes.
     */
    private function loadVendorRoutes() : void
    {
        try {
            // Migrate
            $this->get('/migrations/migrate', [Migrator::class, 'migrate'])->name('migrator-migrate');

            // Rollback
            $this->get('/migrations/rollback/[i:steps]?', [Migrator::class, 'rollback'])->name('migrator-rollback');

            // Seed the DB
            $this->get('/migrations/seed', [Migrator::class, 'seed'])->name('migrator-seed');

            // reset the DB
            $this->get('/migrations/purge', [Migrator::class, 'queryPurge'])->name('migrator-purge-query');
            $this->get('/migrations/purge/confirm', [Migrator::class, 'purge'])->name('migrator-purge');
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
            if (! is_array($match['target'])) {
                // Unknown route target. Other features such as inline functions are not supported (for now).
                throw new RouterConfigurationException;
            }

            $controllerName = $match['target'][0];
            $method = $match['target'][1] ?? 'index';
            $middleware = $match['target'][2];

            // Attempt to make a new instance of the controller
            $injector = new Injector($controllerName);
            $controller = $injector->provide();

            $this->match = new MatchedRoute($controller, $method, $match['params'], $middleware);

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