<?php

use Groundwork\Database\Model;
use Groundwork\Response\View;
use Groundwork\Router\Router;
use Groundwork\Utils\Table;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Gets the root path of the project.
 *
 * @return string
 */
function root() : string
{
    // document root *should* be in ./public, so going up 1 directory will bring us to the project root.
    return $_SERVER['DOCUMENT_ROOT'] . '/../';
}

/**
 * Returns a new instance of a View with the path and data objects.
 * 
 * @param string $path The path to the template file (optional extension)
 * @param array  $data The data to pass into the template engine
 * 
 * @return View
 */
function view(string $path, array $data = []) : View
{
    return new View($path, $data);
}

/**
 * Returns a fresh HTTP request object
 * 
 * @return Request
 */
function request() : Request
{
    return Request::createFromGlobals();
}

/**
 * Creates a new Response object
 * 
 * @param mixed $content The response data
 * @param int   $status  The response status code
 * @param array $headers An array of response headers
 * 
 * @return Response
 */
function response(?string $content = '', int $status = 200, array $headers = []) : Response
{
    return new Response($content, $status, $headers);
}

/**
 * Creates a new Json Response object which extends a Response object.
 * 
 * @param mixed $data    The response data
 * @param int   $status  The response status code
 * @param array $headers An array of response headers
 * @param bool  $json    If the data is already a JSON string
 * 
 * @return JsonResponse
 */
function jsonResponse($data = null, int $status = 200, array $headers = [], bool $json = false) : JsonResponse
{
    return new JsonResponse($data, $status, $headers, $json);
}

/**
 * Creates a new Redirection response object which extends a Response object.
 * 
 * @param string $url     The URL to redirect to
 * @param int    $status  The response status
 * @param array  $headers An array of response headers
 * 
 * @return RedirectResponse
 */
function redirect(string $url, int $status = 302, array $headers = []) : RedirectResponse
{
    return new RedirectResponse($url, $status, $headers);
}

/**
 * Starts a new session instance and returns it.
 * 
 * @return Session
 */
function session() : Session
{
    return new Session();
}

/**
 * Generates a URL for a given route name, with any arguments.
 *
 * @param string      $name The route name
 * @param array|Model $data An associate array with all the params, or the Model (which extracts its identifier)
 *
 * @return string
 * @throws Exception if the route name is invalid.
 */
function route(string $name, $data = []) : string
{
    // Fetch the server instance which has the routes mapped.
    $router = Router::getInstance();

    if ($data instanceof Model) {
        // Extract the models identifier
        $data = ['id' => $data->getIdentifier()];
    }

    // Generate the URL and return it.
    return $router->getRoute($name, $data);
}

/**
 * Attempts to get the name of the current route.
 *
 * @return string|null
 */
function route_name() : ?string
{
    $router = Router::getInstance();

    return $router->getRouteName();
}

/**
 * Attempts to get an array of the current route parameters.
 *
 * @return array|null
 */
function route_params() : ?array
{
    $router = Router::getInstance();

    return $router->getParams();
}


/**
 * Get the class "basename" of the given object / class.
 *
 * @param  string|object  $class
 * @return string
 */
function class_basename($class) : string
{
    $class = is_object($class) ? get_class($class) : $class;

    return basename(str_replace('\\', '/', $class));
}

/**
 * Clamps a number between a min and max value.
 *
 * @param numeric $value The value to clamp
 * @param numeric $min   The minimum value
 * @param numeric $max   The maximum value
 *
 * @return numeric
 */
function clamp($value, $min, $max) {
    if ($min > $max) {
        throw new InvalidArgumentException("$min cannot be larger than $max");
    }

    return max($min, min($max, $value));
}

/**
 * Creates a new instance of a Table, if it isn't already.
 * 
 * @param mixed $data The data to put in the table. It may be a Table, array or otherwise.
 * 
 * @return Table
 */
function table($data = []) : Table
{
    if ($data instanceof Table) {
        return $data;
    }
    
    if (!is_array($data)) {
        $data = [$data];
    }

    return Table::make($data);
}

/**
 * Gets the value of a form input if it passed through a validator.
 *
 * @param string      $name
 * @param string|null $default
 *
 * @return mixed|null
 */
function old(string $name, string $default = null)
{
    $old = session()->get('validation-old');
    if (is_null($old)) {
        return $default;
    }

    $path = request()->getRequestUri();

    return $old[$path][$name] ?? $default;
}

/**
 * Returns whether a given name failed to validate, and the description of the failure.
 *
 * @param string $name
 *
 * @return false|string
 */
function invalid(string $name)
{
    $failed = session()->get('validation-failed');
    if (is_null($failed)) {
        return false;
    }

    $path = request()->getRequestUri();

    return $failed[$path][$name] ?? false;
}

