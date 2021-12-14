<?php

namespace Groundwork\Twig;

use Groundwork\Config\Config;
use Groundwork\Extensions\ExtensionHandler;
use Groundwork\Response\View;
use Groundwork\Traits\Singleton;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Engine
{
    use Singleton;

    /** @var Environment The Twig environment */
    protected Environment $twig;

    private function __construct()
    {
        $this->init();
    }

    /**
     * Loads up Twig and adds the functions.
     */
    protected function init()
    {
        $loader = new FilesystemLoader([
            root() . 'resources/views', // Load from resources first to make views overridable (such as the pagination view)
            __DIR__ . '/../Views' // src/Groundwork/Twig/../Views
        ]);

        $this->twig = new Environment($loader, [
            'cache' => Config::get('VIEW_CACHE', true) ? root() . 'cache/twig' : false,
            'strict_variables' => false,
            'debug' => Config::get('VIEW_DEBUG', Config::isDev())
        ]);

        $this->twig->addFunction(new TwigFunction('old', fn(string $name, string $default = null)  => old($name, $default)));
        $this->twig->addFunction(new TwigFunction('hasError', fn(string $name)  => hasError($name)));
        $this->twig->addFunction(new TwigFunction('getError', fn(string $name)  => getError($name)));

        ExtensionHandler::loadExtension("Renderer", $this->twig);
    }

    /**
     * @param View $view
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(View $view) : string
    {
        return $this->twig->render($view->path(), $view->data());
    }
}