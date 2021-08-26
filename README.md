
# Groundwork
### A lightweight PHP framework

# Note: Groundwork is still in its alpha state and will undergo heavy changes before a more stable release structure is implemented.

This is the core library for the Groundwork project. See [the default Groundwork project](https://github.com/ThijmenDF/groundwork-project) for more information.

The framework uses various external packages such as, but not limited to:
* [Twig](https://packagist.org/packages/twig/twig) for rendering templates.
* [Symfony](https://packagist.org/packages/symfony/http-foundation) for HTTP request / response processing.
* [Carbon](https://packagist.org/packages/nesbot/carbon) for date / time.
* [Altorouter](https://packagist.org/packages/altorouter/altorouter) for routes.
* [TailwindCSS](https://tailwindcss.com/) for styling. You'll have to install it with npm. (see package.json in the default project)

### Installing
To install, run `composer require thijmendf/groundwork`

### Initializing
In order to start the framework, send all http requests (except for requests for files such as assets) to a single php file.
In that file, you can use this code to start the framework:
```php
// File location: ./public/index.php

// Require the auto-loader
require __DIR__ . '../vendor/autoload.php';

use Groundwork\Server;

// Start the handle server
$server = Server::getInstance();

// Handle the request
$server->handle();
```

And that's it. It'll automatically handle the requests and responses.

### Default file structure

A project that implements this framework needs the following file structure:
```
+ project
|---+ App <- This is where the main source code of your project will go to
|   |---+ Models <- Database models
|   |
|   |---+ Controllers <- View controllers
|   |
|   |---+ Extensions <- Extending Groundwork
|   |
|   |---+ Requests <- Validating form requests
|
|---+ cache <- Caching various systems such as the templates
|
|---+ database
|   |---+ migrations <- Migrations that are run once. Doesn't have to be database related
|   |
|   |---+ seeders <- Seeders for the database
|
|---+ public
|   |---+ assets <- Place for css, js, images, fonts etc.
|   |
|   |---- index.php <- All requests must go here. See code above
|
|---+ resources <- Root for uncompiled assets (views, css, js etc.)
|   |---+ views <- Root for templates
|
|---+ routes <- Here all routers can be added. 
|               File names don't matter, as long as thei're php files.
|---- .env <- Configuration file. see .env.example
```

### Extending
You can also extend certain features by making a class in the `/App/Extensions` namespace. Extensions **must** implement the `Groundwork/Extensions/Extension` interface.

The following components can be extended:
* `App/Extensions/Renderer` For adding twig functions, filters etc.
* More coming soon.

See [the default Groundwork project](https://github.com/ThijmenDF/groundwork-project) for an example of how this framework can be set up.