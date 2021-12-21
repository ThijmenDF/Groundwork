<?php

use Groundwork\Config\Config;
use Groundwork\Database\Model;
use Groundwork\Request\Request;
use Groundwork\Response\RedirectResponse;
use Groundwork\Response\Response;
use Groundwork\Response\View;
use Groundwork\Router\Router;
use Groundwork\Utils\Table;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Gets the root path of the project.
 *
 * @return string
 */
function root() : string
{
    return Config::get('ROOT_DIR');
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
    return Request::getInstance();
}

/**
 * Creates a new Response object
 *
 * @param mixed $content The response data
 * @param int   $status  The response status code
 *
 * @return Response
 */
function response(?string $content = '', int $status = 200) : Response
{
    return new Response($content, $status);
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
    return (new RedirectResponse($url, $status))->withHeaders($headers);
}

/**
 * Creates a new Redirection response object which redirects to the current page.
 *
 * @param int   $status  The response status
 * @param array $headers An array of response headers
 *
 * @return RedirectResponse
 */
function reload(int $status = 302, array $headers = []) : RedirectResponse
{
    return redirect(request()->url(), $status, $headers);
}

/**
 * Creates a new Redirection response object which attempts to send the user back to their previous location.
 *
 * @return RedirectResponse
 */
function back() : RedirectResponse
{
    return redirect(request()->referer() ?? request()->url());
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
    // Fetch the router instance which has the routes mapped.
    $router = Router::getInstance();

    if (! is_array($data)) {
        $data = ['id' => $data];
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
 * @return string|null
 */
function old(string $name, string $default = null) : ?string
{
    return request()->old($name, $default);
}

/**
 * Returns whether a given name is known as an error.
 *
 * @param string $name
 *
 * @return bool
 */
function hasError(string $name) : bool
{
    $failed = request()->getFlashItem('errors');

    return $failed[$name] ?? false;
}

/**
 * Returns the text content of a given error name, and clears it from the flash session.
 *
 * @param string $name
 *
 * @return string|null
 */
function getError(string $name) : ?string
{
    $errors = request()->getFlashItem('errors');

    return $errors[$name] ?? null;
}
