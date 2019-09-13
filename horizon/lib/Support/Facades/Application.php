<?php

namespace Horizon\Support\Facades;

use Horizon\Exception\HorizonException;
use Horizon\Support\Services\ServiceProvider;

/**
 * Facade for access to application-specific information and methods.
 */
class Application
{

    /**
     * Returns the service container for the application.
     *
     * @return \Horizon\Support\Container\Container
     */
    public static function container()
    {
        return \Horizon\Foundation\Application::container();
    }

    /**
     * Registers a service provider in the application.
     *
     * @param ServiceProvider $provider
     * @return void
     */
    public static function register(ServiceProvider $provider)
    {
        static::container()->register($provider);
    }

    /**
     * Returns an absolute path to the application's root directory. If a relative path is specified, the returned path
     * will be an absolute path to the specified location within the application.
     *
     * @param string $relative
     * @return string
     */
    public static function path($relative = '')
    {
        return \Horizon\Foundation\Application::path($relative);
    }

    /**
     * Returns a path to an asset in the `app/public` folder intended for use in link, script, and image references on
     * the outputted pages.
     *
     * @param string $relative
     * @return string
     */
    public static function asset($relative = '')
    {
        return \Horizon\Foundation\Application::asset($relative);
    }

    /**
     * Gets the current environment in which the application is running (console, test, production).
     *
     * @return string
     */
    public static function environment()
    {
        return \Horizon\Foundation\Application::environment();
    }

    /**
     * Gets the current routing mode (legacy, rewrite, none).
     *
     * @return string
     */
    public static function routing()
    {
        return \Horizon\Foundation\Application::routing();
    }

    /**
     * Gets the current version of the application.
     *
     * @return string
     * @throws HorizonException
     */
    public static function version()
    {
        return \Horizon\Foundation\Application::version();
    }

    /**
     * Gets the value of a configuration entry at the specified key path. The path should be in dot notation, with
     * the first segment containing the name of the configuration file. If the file or key path does not exist, the
     * default value is returned.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws HorizonException
     */
    public static function config($key, $default = null)
    {
        return \Horizon\Foundation\Application::config($key, $default);
    }

}
