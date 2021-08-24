<?php

namespace Groundwork\Config;

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;

abstract class Config {

    public static function load()
    {
        $dotenv = Dotenv::createImmutable(root());
        $dotenv->load();

        static::checkRequired($dotenv);
    }

    /**
     * Makes sure all required .env keys are present.
     * 
     * @param Dotenv $dotenv
     * @throws ValidationException
     */
    protected static function checkRequired(Dotenv $dotenv)
    {
        $dotenv->required('APP_ENV')
            ->notEmpty()
            ->allowedValues(['prod', 'dev', 'test']);

        $dotenv->ifPresent(['DB_HOST', 'DB_USER'])
            ->notEmpty();

        $dotenv->ifPresent('DB_PORT')
            ->isInteger();

        $dotenv->required('DB_NAME')
            ->notEmpty();

        $dotenv->required('DB_PASS');
    }

    /**
     * Gets a specific key from the .env file.
     *
     * @param string     $name
     * @param mixed|null $default
     *
     * @return string|bool|int|null
     */
    public static function get(string $name, $default = null) {
        return $_ENV[$name] ?? $default;
    }

    /**
     * Returns whether the current environment is production (live).
     * 
     * @return bool
     */
    public static function isProd() : bool
    {
        return static::get('APP_ENV') === 'prod';
    }

    /**
     * Returns whether the current environment is test.
     * 
     * @return bool
     */
    public static function isTest() : bool
    {
        return static::get('APP_ENV') === 'test';
    }

    /**
     * Returns whether the current environment is development.
     * 
     * @return bool
     */
    public static function isDev() : bool
    {
        return static::get('APP_ENV') === 'dev';
    }


}