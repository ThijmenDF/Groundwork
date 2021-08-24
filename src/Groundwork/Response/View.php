<?php

namespace Groundwork\Response;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Groundwork\Utils\Str;
use Twig\TwigFunction;

class View {

    /**
     * The Twig Environment.
     */
    private Environment $twig;

    /**
     * The path of the template to render.
     */
    private string $path;

    /**
     * The data array to pass into the render method.
     */
    private array $data;

    /**
     * Sets up the twig environment and loads up the proper path.
     */
    public function __construct($path, $data = [])
    {
        $loader = new FilesystemLoader([
            root() . '/resources/views', // Load from resources first to make views overridable (such as the error views)
            root() . '/resources/views/Groundwork'
        ]);

        $this->twig = new Environment($loader, [
            'cache' => false,
            'strict_variables' => false,
        ]);

        $this->twig->addFunction(new TwigFunction('old', fn(string $name, string $default = null)  => old($name, $default)));
        $this->twig->addFunction(new TwigFunction('invalid', fn(string $name)  => invalid($name)));

        if (!Str::endsWith($path, '.html.twig')) {
            $path .= '.html.twig';
        }

        $this->path = $path;

        $this->data = $data;
    }

    /**
     * Runs the render method and returns its result.
     *
     * @throws LoaderError
     * @throws SyntaxError
     * @throws RuntimeError
     */
    public function handle() : string
    {
        return $this->twig->render($this->path, $this->data);
    }
    
}