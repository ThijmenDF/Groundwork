# Groundwork
### A lightweight PHP framework

This is the core library for the Groundwork project.

To install, run 
`composer require thijmendf/groundwork`

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

And that's it. It'll automatically handle the requests and responses. See the default project for more information.


See [the default Groundwork project](https://github.com/ThijmenDF/groundwork-project) on github on how to get started.