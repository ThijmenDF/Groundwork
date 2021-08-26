<?php

namespace Groundwork\Response;

use Groundwork\Utils\Table;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response
{

    protected SymfonyResponse $response;

    /** @var mixed The response content, before serialisation. */
    protected $content;

    /**
     * @param mixed $content
     */
    public function __construct($content, int $code = 200)
    {
        $this->response = new SymfonyResponse();

        $this->code($code);

        $this->content = $content;
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $code
     *
     * @return $this
     */
    public function code(int $code) : self
    {
        $this->response->setStatusCode($code);

        return $this;
    }

    /**
     * Set a specific header
     *
     * @param string $name
     * @param        $value
     *
     * @return $this
     */
    public function header(string $name, $value) : self
    {
        $this->response->headers->set($name, $value);

        return $this;
    }

    /**
     * Set a list of headers. Accepts an associative array where the keys are names and values are values.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function withHeaders(array $headers) : self
    {
        foreach($headers as $key => $value) {
            $this->header($key, $value);
        }

        return $this;
    }

    /**
     * Adds a cookie.
     *
     * @param string $name     Cookie name
     * @param string $value    Cookie value
     * @param int    $minutes  Expire time
     * @param string $path     Cookie Path
     * @param string $domain   Cookie Domain
     * @param bool   $secure   Only secure (https) cookies
     * @param bool   $httpOnly Cookie may only be used for http requests
     *
     * @return $this
     */
    public function cookie(string $name, string $value, int $minutes, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false) : Response
    {
        setcookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly);

        return $this;
    }

    /**
     * Clears a specific cookie.
     *
     * @param string $name
     *
     * @return $this
     */
    public function withoutCookie(string $name) : self
    {
        $this->cookie($name, '', -1);

        return $this;
    }

    /**
     * Sets the response content.
     *
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content) : self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Parses the response content and returns a Symfony Response object.
     *
     * @return SymfonyResponse
     */
    public function get() : SymfonyResponse
    {
        $this->response->setContent($this->processContent());

        return $this->response;
    }

    /**
     * Processes the content and returns it as a string.
     *
     * @return string
     */
    protected function processContent() : string
    {
        switch (true) {
            case is_null($this->content):
                return '';
            case is_string($this->content):
                return $this->content;
//            case $this->content instanceof Model:
//                return $this->content->toJson();
            case $this->content instanceof Table:
                return json_encode($this->content->all());
            default:
                return (string) $this->content;
        }
    }

}