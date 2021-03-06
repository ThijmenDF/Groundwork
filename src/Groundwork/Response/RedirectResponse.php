<?php

namespace Groundwork\Response;

use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RedirectResponse extends Response
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(string $url, int $code = 302)
    {
        $this->response = new SymfonyRedirectResponse($url, $code);
    }

    public function get() : SymfonyResponse
    {
        return $this->response;
    }
}