<?php

namespace Groundwork;

use Error;
use Exception;
use Groundwork\Config\Config;
use Groundwork\Container\Container;
use Groundwork\Database\Db;
use Groundwork\Database\Pagination\PaginatedResult;
use Groundwork\Exceptions\EnvConfigurationException;
use Groundwork\Exceptions\Http\HttpException;
use Groundwork\Exceptions\Http\InternalServerErrorException;
use Groundwork\Exceptions\ValidationFailedException;
use Groundwork\Extensions\ExtensionHandler;
use Groundwork\Response\Response;
use Groundwork\Response\View;
use Groundwork\Router\MatchedRoute;
use Groundwork\Router\Router;
use Groundwork\Traits\Singleton;
use Groundwork\Twig\Engine;

class Server {

    use Singleton;

    /**
     * Holds the container instance.
     *
     * @var Container
     */
    protected Container $container;

    private function __construct(string $rootDirectory = null)
    {
        try {
            new Config(rtrim($rootDirectory, '/') . '/');
        } catch (EnvConfigurationException $exception) {
            $this->processResult(new InternalServerErrorException('Incorrect environment configuration! - ' . $exception->getMessage()));
            exit;
        }

        $this->bootstrap();

        if (!Config::isProd()) {
            // Set up the error handler only if not running in production.
            $this->setupWhoops();
        }

    }

    /**
     * Starts the application's container instance and registers some of its features.
     *
     * Also loads the 'bootstrap' extension handler and gives it the raw container instance.
     *
     * @return void
     */
    protected function bootstrap()
    {
        $this->container = new Container();

        // Register the router, and make a new instance of it.
        $this->container->register('router', new Router());

        // Register the database client.
        $this->container->register('db', Db::class);

        // Register the twig render engine.
        $this->container->register('twig', Engine::class);

        ExtensionHandler::loadExtension('bootstrap', $this->container);
    }

    /**
     * Returns the instance of the given identifier, or sets a new instance.
     *
     * @param string             $identifier
     * @param object|string|null $instance A new instance to save with the identifier.
     *
     * @return mixed|null
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function container(string $identifier, $instance = null)
    {
        if ($instance) {
            $this->container->register($identifier, $instance);
            return true;
        } else {
            return $this->container->get($identifier);
        }
    }

    /**
     * Runs the route matching and runs the controller method
     *
     * @throws Exception
     */
    public function handle() : void
    {
        try {
            /** @var MatchedRoute $match */
            $match = instance('router')->matchRoutes();

            $result = $match->call();
        } catch (HttpException $ex) {
            $result = $ex;
            error_log((string) $ex);
        } catch (ValidationFailedException $ex) {
            // This is thrown if the dependency injection ran a Validator which returned on failure
            $result = back();
        } catch (Exception $ex) {
            if (Config::isDev()) {
                throw $ex;
            }
            $result = new InternalServerErrorException($ex->getMessage());

            error_log((string) $ex);
        } catch (Error $ex) {
            if (Config::isDev()) {
                throw $ex;
            }
            $result = new InternalServerErrorException($ex->getMessage());

            error_log((string) $ex);
        }

        $this->processResult($result);
    }

    /**
     * Processes the result. Based on what the result was, the script may do different things.
     *
     * @param mixed    $result
     * @param int|null $code
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function processResult($result, int $code = null) : void
    {
        switch (true) {
            case $result instanceof View:
                // Parse the View and re-run this method with the content as a response
                $engine = instance('twig');

                $result = response($engine->render($result));
                break;
            case $result instanceof PaginatedResult:
                // create a json response
                $result = jsonResponse($result);
                break;
            case $result instanceof HttpException:
                // Prepare the View for this exception if the error handler didn't catch it.
                $code = $code ?? $result->getCode() ?? 500;
                $result = $result->toView();
                break;
            case is_string($result):
                // Simply return it as a string response.
                $result = response($result);
                break;
        }

        if ($result instanceof Response) {

            // Submit the result with the use of the Response Class
            if (! is_null($code)) {
                $result->code($code);
            }

            $result
                ->get()
                ->prepare(request()->getRequest())
                ->send();
        } else {
            echo $result;
        }

    }

    /**
     * Registers the Whoops error handler
     */
    private function setupWhoops() : void
    {
        $run = new \Whoops\Run;
        $handler = new \Whoops\Handler\PrettyPageHandler;

        $run->appendHandler($handler);

        if (request()->isAjaxRequest()) {
            $run->prependHandler(new \Whoops\Handler\JsonResponseHandler);
        }

        $run->register();
    }

}