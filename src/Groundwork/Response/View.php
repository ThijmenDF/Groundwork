<?php

namespace Groundwork\Response;

use Groundwork\Utils\Str;

class View {

    /**
     * The path of the template to render.
     */
    protected string $path;

    /**
     * The data array to pass into the render method.
     */
    protected array $data;

    /**
     * Sets up the twig environment and loads up the proper path.
     */
    public function __construct(string $path, array $data = [])
    {
        if (!Str::endsWith($path, '.html.twig')) {
            $path .= '.html.twig';
        }

        $this->path = $path;

        $this->data = $data;
    }

    /**
     * @return string
     */
    public function path() : string
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function data() : array
    {
        return $this->data;
    }
}