<?php

namespace Groundwork\Request;

use Groundwork\Injector\Injection;
use Groundwork\Traits\Singleton;
use Groundwork\Utils\Table;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\Session;

class Request implements Injection
{
    use Singleton;

    protected SymfonyRequest $request;

    protected ?Session $session = null;

    protected array $flashed = [];

    protected Table $files;

    protected function __construct()
    {
        $this->request = SymfonyRequest::createFromGlobals();

        $this->files = FileUpload::createFromGlobal();
    }

    public static function __inject($param) : self
    {
        return static::getInstance();
    }

    /**
     * Allows any call to unknown methods to be passed to the internal request object.
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->request->{$name}(...$arguments);
    }

    /**
     * Gets the Symfony Request object.
     *
     * @return SymfonyRequest
     */
    public function getRequest() : SymfonyRequest
    {
        return $this->request;
    }

    /**
     * Returns the path without query string.
     *
     * @return string
     */
    public function path() : string
    {
        return $this->request->getPathInfo();
    }

    /**
     * Returns the path with query string.
     *
     * @return string
     */
    public function url() : string
    {
        return $this->request->getRequestUri();
    }

    /**
     * Returns the full URL with protocol, host, port, path and query string.
     *
     * @return string
     */
    public function fullUrl() : string
    {
        return $this->request->getUri();
    }

    /**
     * Returns the request method which may get overwritten by the _method parameter or X-HTTP-Method-Override header.
     *
     * @return string
     */
    public function method() : string
    {
        $this->request->enableHttpMethodParameterOverride();

        return $this->request->getMethod();
    }

    /**
     * Returns the actual request method that can't be overwritten by the _method parameter or X-HTTP-Method-Override
     * header.
     *
     * @return string
     */
    public function realMethod() : string
    {
        return $this->request->getRealMethod();
    }

    /**
     * Returns the value of a given header by name, or the default.
     *
     * @param string $name
     * @param null   $default
     *
     * @return string|null
     */
    public function header(string $name, $default = null) : ?string
    {
        return $this->request->headers->get($name, $default);
    }

    /**
     * Returns whether the given header name exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name) : bool
    {
        return $this->request->headers->has($name);
    }

    /**
     * Attempts to get the remote IP address.
     *
     * @return string|null
     */
    public function ip() : ?string
    {
        return $this->request->getClientIp();
    }

    /**
     * Gets a list of all request data from the POST method.
     *
     * @return array
     */
    public function all() : array
    {
        return $this->request->request->all();
    }

    /**
     * Gets a particular value from any request, albeit Post or Get.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function input(string $name, $default = null)
    {
        return $this->request->get($name, $default);
    }

    /**
     * Gets the given key from the GET data.
     *
     * @param string $name
     * @param null   $default
     *
     * @return bool|float|int|string|null
     */
    public function query(string $name, $default = null)
    {
        return $this->request->query->get($name, $default);
    }

    /**
     * Returns whether the request has a value that matches a 'true-ish' value, aka 1, true, on or yes.
     *
     * @param string $name
     *
     * @return bool
     */
    public function boolean(string $name) : bool
    {
        return $this->request->request->getBoolean($name) || $this->request->query->getBoolean($name);
    }

    /**
     * Returns whether the request has a key with the given name or array of names.
     *
     * @param string|string[] $name
     *
     * @return bool
     */
    public function has($name) : bool
    {
        if (is_string($name)) {
            return $this->request->request->has($name) || $this->request->query->has($name);
        }

        return table($name)->every(fn($value) => $this->has($value));
    }

    /**
     * Returns whether the given item is filled (has data and isn't 0).
     *
     * @param string $name
     *
     * @return bool
     */
    public function filled(string $name) : bool
    {
        return $this->has($name) && !empty($this->input($name));
    }

    /**
     * Returns whether the given key misses from the request. Opposite of `->has()`
     *
     * @param string $name
     *
     * @return bool
     */
    public function missing(string $name) : bool
    {
        return !$this->has($name);
    }

    /**
     * Attempts to get the first File input by key name.
     *
     * @param string $name
     *
     * @return FileUpload|null
     */
    public function file(string $name) : ?FileUpload
    {
        return $this->files->first(fn(FileUpload $upload) => $upload->inputName() === $name);
    }

    /**
     * Returns whether a given file is present based on their input key name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasFile(string $name) : bool
    {
        return $this->files->contains(fn(FileUpload $upload) => $upload->inputName() === $name);
    }

    /**
     * Retrieves a table of all FileUpload instances.
     *
     * @return Table
     */
    public function files() : Table
    {
        return $this->files;
    }

    /**
     * Gets the value from a cookie.
     *
     * Hmm! Cookies! Me LIKE Cookies!! /s
     *
     * @param string $name
     *
     * @return bool|float|int|string|null
     */
    public function cookie(string $name)
    {
        return $this->request->cookies->get($name);
    }

    /**
     * Gets the session instance.
     *
     * @return Session
     */
    public function session() : Session
    {
        if (is_null($this->session)) {
            $this->session = session();
        }

        return $this->session;
    }

    /**
     * Saves the old input values in the flash session.
     *
     * @return bool
     */
    public function flash() : bool
    {
        $bag = $this->session()->getFlashBag();

        $bag->set('old', $this->all());

        return true;
    }

    /**
     * Gets an item from the flash session.
     *
     * @param string $name The collection name to get.
     *
     * @return array Which may be an empty array if the requested collection doesn't exist.
     */
    public function getFlashItem(string $name) : array
    {
        if (empty($this->flashed)) {
            $this->flashed = $this->session()->getFlashBag()->all();
        }

        return $this->flashed[$name] ?? [];
    }

    /**
     * Gets a value from the 'old input' flash session.
     *
     * @param string     $name
     * @param mixed|null $default
     *
     * @return string|null
     */
    public function old(string $name, $default = null) : ?string
    {
        return $this->getFlashItem('old')[$name] ?? $default;
    }

    /**
     * Attempts to get the url of the referer header, if it exists.
     *
     * @return string|null
     */
    public function referer() : ?string
    {
        return $this->header('referer');
    }

    /**
     * Returns whether the request was made using some kind of tool (X-Requested-With must be set).
     *
     * @return bool
     */
    public function isAjaxRequest() : bool
    {
        return !is_null($this->header('X-Requested-With'));
    }

}