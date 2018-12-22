<?php

namespace Horizon\Framework;

use Horizon\Framework\Core\Configuration;
use Horizon\Support\Path;
use Horizon\Support\Services\ServiceObjectCollection;
use Horizon\Support\Services\ServiceProvider;
use Horizon\Exception\HorizonException;

/**
 * The base application class.
 */
class Application
{

    /**
     * @var Kernel
     */
    private static $kernel;

    /**
     * All service providers registered to the application.
     *
     * @var ServiceProvider[]
     */
    private static $providers = array();

    /**
     * Maps provided classes (as the key) to all of their providers (array value).
     *
     * @var ServiceProvider[][]
     */
    private static $providersMap = array();

    /**
     * Stores an array of service providers that have been booted.
     * @var ServiceProvider[]
     */
    private static $booted = array();

    /**
     * Stores an array of cached resolutions.
     * @var ServiceObjectCollection[]
     */
    private static $cache = array();

    /**
     * Registers a service provider in the application.
     *
     * @param ServiceProvider $provider
     * @return void
     */
    public static function register(ServiceProvider $provider)
    {
        static::$providers[] = $provider;

        foreach ($provider->provides() as $className) {
            // Make sure the array exists
            if (!array_key_exists($className, static::$providersMap)) {
                static::$providersMap[$className] = array();
            }

            // Do not continue if the provider is already in the array
            if (in_array($provider, static::$providersMap[$className])) continue;

            // Add the provider to the class name mapping
            static::$providersMap[$className][] = $provider;
        }
    }

    /**
     * Boots the service providers registered to the application.
     */
    public static function boot()
    {
        foreach (static::$providers as $provider) {
            if ($provider->isDeferred()) continue;
            if (in_array($provider, static::$booted)) continue;

            $provider->boot();
            static::$booted[] = $provider;
        }
    }

    /**
     * Gets a collection of service objects derived from the given class name.
     *
     * @param string $className
     * @param bool $allowCachedResolution
     * @return ServiceObjectCollection
     */
    public static function resolve($className, $allowCachedResolution = true)
    {
        $collection = null;

        // Resolve from cache
        if ($allowCachedResolution && array_key_exists($className, static::$cache)) {
            return static::$cache[$className];
        }

        // Resolve from service providers
        if (array_key_exists($className, static::$providersMap)) {
            $providers = static::$providersMap[$className];
            $objects = array();

            foreach ($providers as $provider) {
                if ($provider->isDeferred() && !in_array($provider, static::$booted)) {
                    $provider->boot();
                }

                foreach ($provider->resolve($className) as $resolved) {
                    if ($resolved instanceof $className) {
                        $objects[] = $resolved;
                    }
                }
            }

            $collection = new ServiceObjectCollection($objects);
        }

        if (is_null($collection)) {
            $collection = new ServiceObjectCollection();
        }

        static::$cache[$className] = $collection;
        return $collection;
    }

    /**
     * Gets an absolute path to the application's root directory. If a relative path is specified, the returned path
     * will be an absolute path to the specified location within the application.
     *
     * @param string $relative
     * @return string
     */
    public static function path($relative = '')
    {
        $relative = ltrim($relative, '\\/');

        return Path::join(FRAMEWORK_HORIZON_ROOT, $relative);
    }

    /**
     * Gets the current environment in which the application is running (console, test, web).
     *
     * @return string
     */
    public static function environment()
    {
        if (($environment = getenv('HORIZON_ENVIRONMENT')) === false) {
            if (defined('CONSOLE_MODE')) return 'console';
            if (defined('USE_LEGACY_ROUTING')) return 'production';

            return 'unknown';
        }

        return $environment;
    }

    /**
     * Gets the current routing mode (legacy, rewrite, none).
     *
     * @return string
     */
    public static function routing()
    {
        if (defined('USE_LEGACY_ROUTING')) {
            return USE_LEGACY_ROUTING ? 'legacy' : 'rewrite';
        }

        return 'none';
    }

    /**
     * Gets the current version of the application.
     *
     * @return string
     * @throws HorizonException
     */
    public static function version()
    {
        return static::config('app.version', '1.0');
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
        return Configuration::get($key, $default);
    }

    /**
     * Gets the primary kernel for the application and framework.
     *
     * @return Kernel
     */
    public static function kernel()
    {
        return static::$kernel ?: (static::$kernel = new Kernel());
    }

}
