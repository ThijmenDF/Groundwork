<?php

namespace Groundwork;

use Dotenv\Exception\ValidationException;
use Error;
use Exception;
use Groundwork\Config\Config;
use Groundwork\Database\Pagination\PaginatedResult;
use Groundwork\Exceptions\Http\HttpException;
use Groundwork\Exceptions\Http\InternalServerErrorException;
use Groundwork\Exceptions\ValidationFailedException;
use Groundwork\Injector\Injector;
use Groundwork\Response\View;
use Groundwork\Router\Router;
use Groundwork\Traits\Singleton;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Util\Misc;

class Server {

    use Singleton;
    
    // The router that loads up routes
    private Router $router;

    private function __construct()
    {
        try {
            Config::load();
        } catch (ValidationException $exception) {
            $this->processResult(new InternalServerErrorException('Incorrect environment configuration! - ' . $exception->getMessage()));
            exit;
        }

        if (!Config::isProd()) {
            // Set up the error handler only if not running in production.
            $this->setupWhoops();
        }

        // Load up the router
        $this->router = Router::getInstance();
    }

    /**
     * Runs the route matching and runs the controller method
     *
     * @throws Exception
     */
    public function handle() : void
    {
        try {
            $match = $this->router->matchRoutes();

            $reflector = new Injector($match[0]);

            try {
                $result = $reflector->provide($match[1], $match[2]);
            } catch (ValidationFailedException $exception) {
                $result = $exception;
            }
        }
        catch (HttpException $ex) {
            $result = $ex;
            error_log($ex->__toString());
        } 
        catch (Exception $ex) {
            if (!Config::isProd()) {
                throw $ex;
            }
            $result = new InternalServerErrorException($ex->getMessage());
            error_log($ex->__toString());
        } 
        catch (Error $error) {
            if (!Config::isProd()) {
                throw $error;
            }
            $result = new InternalServerErrorException($error->getMessage());
            error_log($error->__toString());
        }

        $this->processResult($result);
    }

    /**
     * Processes the result. Based on what the result was, the script may do different things.
     *
     * @param mixed    $result
     * @param int|null $code
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function processResult($result, int $code = null) : void
    {
        switch (true) {
            case $result instanceof View:
                // Parse the View and re-run this method with the content as a response

                $content = $result->handle();

                $this->processResult(response($content), $code);

                break;
            case $result instanceof Response:
                // Submit the result with the use of the Response Class
                $result
                    ->setStatusCode($code ?? $result->getStatusCode())
                    ->prepare(request())
                    ->send();

                break;
            case $result instanceof PaginatedResult:
                // create a json response
                $this->processResult(jsonResponse($result), $code);

                break;
            case $result instanceof HttpException:
                // Prepare the View for this exception if the error handler didn't catch it.
                $this->processResult($result->toView(), $code ?? $result->getCode() ?? 500);

                break;
            case $result instanceof ValidationFailedException:

                $this->processResult(redirect(
                    request()->getRequestUri()
                ));

                break;
            case is_string($result):
                // Simply return it as a string response.
                $this->processResult(response($result), $code);

                break;
            default:
                echo $result;
        }
    }

    /**
     * Registers the Whoops error handler
     */
    private function setupWhoops() : void
    {
        $run = new Run;
        $handler = new PrettyPageHandler;

        $handler->setPageTitle("Whoops! You made a mistake");

        $run->pushHandler($handler);

        if (Misc::isAjaxRequest()) {
            $run->pushHandler(new JsonResponseHandler);
        }

        $run->register();
    }

}