# Contributing

Thank you for checking out Groundwork! Everyone is welcome to come up with new ideas or changes. 
Check the [discussions](https://github.com/ThijmenDF/Groundwork/discussions) for things like to-do lists and the [issues](https://github.com/ThijmenDF/Groundwork/issues) for any bugs that may need fixing.

## Making changes

If you wish to make changes, you can create a new branch. These branches can be grouped based on their role:

* `Feature/<name>` can be used for if you wish to add new functionality to the project.
* `Bug/<name>` can be used for if you wish to fix an issue.

If you instead make your own fork, the branch names are less strict.

When your changes have been pushed to your branch, you can create a [pull request](https://github.com/ThijmenDF/Groundwork/pulls) to merge to the current development branch (most likely main). I will review your changes and possibly request alterations. Afterwards, I'll merge your changes and add you to the contributors.

## Quality

This project is written with the following ideas in mind:
* The PHP code needs to be compatible with php 7.4. This is the lowest supported PHP version for this framework.
* Code style loosely follows the [PSR-2](https://www.php-fig.org/psr/psr-2/) definition. Some small changes are permitted, such as how arrays can be in-line or how there doesn't need to be a space after a `!` in an if statement.
* It is expected that all methods and attributes have a phpdoc explaining their purpose. If a method or attribute is overridden, the descendant doesn't necessarily need a phpdoc if their signature or intentions do not differ from its parent.

## Setting up your environment

### Prerequisites

A device or virtual machine with the following software:
* PHP 7.4 or newer
* Composer 2
* NPM (for TailwindCSS)

### Cloning 

1. clone this into your local directory
2. In the root folder, run `composer install`
3. Open the root folder with your editor of choice. This project is made using PHPStorm and VSC.
4. All source code goes into `/src`

### Testing

Due to the way this project is set up currently, you cannot run a test server locally. 
To test changes, you need to set up the [default project](https://github.com/ThijmenDF/groundwork-project) alongside this.

1. Clone the default project into another folder (**not** in this project's folder)
2. Install the default project by running `composer install`
3. Remove the contents from `/vendor/thijmendf/groundwork` and remove the `groundwork` directory.
4. Make a symbolic directory link from your development environment to `/vendor/thijmendf/groundwork`. Any changes made in the Groundwork directory will be copied to the default project's `vendor` directory.
5. Start the webserver in the default project. `cd ./public` and `php -S localhost:8080`

In order to test your code, you can add Controllers, Models, Extensions, Migrations etc. in the default project.
