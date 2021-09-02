<?php

namespace Groundwork\Config;

use Groundwork\Exceptions\EnvConfigurationException;
use Groundwork\Extensions\ExtensionHandler;
use Symfony\Component\Dotenv\Dotenv;

class Config {

    /**
     * By creating a new instance, the .env file will be loaded and the 'Config' extension will be run.
     *
     * @throws EnvConfigurationException
     */
    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->load(root() . '.env');

        $this->checkVendorSettings();

        ExtensionHandler::loadExtension('Config', $this);
    }

    /**
     * Makes sure all required .env keys are present for the vendor.
     *
     * @throws EnvConfigurationException
     */
    protected function checkVendorSettings()
    {
        static::required('APP_ENV')
            ->in(['prod', 'test', 'dev']);

        static::optional('DB_HOST')
            ->notEmpty();

        static::optional('DB_USER')
            ->notEmpty();

        static::required('DB_NAME')
            ->notEmpty();

        static::optional('DB_PORT')
            ->integer();

        static::required('DB_PASS');

        static::optional('VIEW_CACHE')
            ->boolean();

        static::optional('DEBUG_CACHE')
            ->boolean();
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
     * Returns whether a given value exists in the .env file.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function has(string $name) : bool
    {
        return isset($_ENV[$name]);
    }

    /**
     * Creates a new validator and makes sure the key exists.
     *
     * @param string $name
     *
     * @return EnvValidator
     * @throws EnvConfigurationException
     */
    public static function required(string $name) : EnvValidator
    {
        return (new EnvValidator($name))->required();
    }

    /**
     * Creates a new validator and makes it optional.
     *
     * @param string $name
     *
     * @return EnvValidator
     */
    public static function optional(string $name) : EnvValidator
    {
        return (new EnvValidator($name))->optional();
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