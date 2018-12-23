<?php

namespace Horizon\Support\Container;

use Horizon\Support\Services\ServiceObjectCollection;
use Horizon\Support\Services\ServiceProvider;

/**
 * A very basic service container implementation which can help resolve dependencies where necessary. It's not very
 * powerful and is only meant to be a drop-in utility where necessary.
 */
class Container
{
    
    /**
     * All service providers registered in the container.
     *
     * @var ServiceProvider[]
     */
    private $providers = array();

    /**
     * Maps provided classes (as the key) to all of their providers (array value).
     *
     * @var ServiceProvider[][]
     */
    private $providersMap = array();

    /**
     * Stores an array of service providers that have been booted.
     * @var ServiceProvider[]
     */
    private $booted = array();

    /**
     * Stores an array of cached resolutions.
     * @var ServiceObjectCollection[]
     */
    private static $cache = array();

    /**
     * Registers a service provider in the container.
     *
     * @param ServiceProvider $provider
     * @return void
     */
    public function register(ServiceProvider $provider)
    {
        $this->providers[] = $provider;

        foreach ($provider->provides() as $className) {
            // Make sure the array exists
            if (!array_key_exists($className, $this->providersMap)) {
                $this->providersMap[$className] = array();
            }

            // Do not continue if the provider is already in the array
            if (in_array($provider, $this->providersMap[$className])) continue;

            // Add the provider to the class name mapping
            $this->providersMap[$className][] = $provider;
        }
    }

    /**
     * Boots the service providers registered in the container.
     */
    public function boot()
    {
        foreach ($this->providers as $provider) {
            if ($provider->isDeferred()) continue;
            if (in_array($provider, $this->booted)) continue;

            $provider->boot();
            $this->booted[] = $provider;
        }
    }

    /**
     * Gets a collection of service objects derived from the given class name.
     *
     * @param string $className
     * @param bool $allowCachedResolution
     * @return ServiceObjectCollection
     */
    public function resolve($className, $allowCachedResolution = true)
    {
        $collection = null;

        // Resolve from cache
        if ($allowCachedResolution && array_key_exists($className, static::$cache)) {
            return static::$cache[$className];
        }

        // Resolve from service providers
        if (array_key_exists($className, $this->providersMap)) {
            $providers = $this->providersMap[$className];
            $objects = array();

            foreach ($providers as $provider) {
                if ($provider->isDeferred() && !in_array($provider, $this->booted)) {
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

}
