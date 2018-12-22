<?php

namespace Horizon\Support\Services;

use InvalidArgumentException;

/**
 * A service provider is a utility class that initializes objects on demand which are necessary to load a service.
 * Note that Horizon service providers differ fundamentally from Laravel's providers.
 *
 * For instance, Laravel includes a full service container for dependency injection; Horizon's providers are meant to be
 * a basic way to dynamically load objects for its services and there is no service container or dependency injection.
 */
class ServiceProvider
{

    /**
     * Indicates if the boot method should be deferred (called immediately before the provider is first invoked).
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Whether the provider has been registered yet.
     * @var bool
     */
    private $registered = false;

    /**
     * Binds between classes and provider callables.
     *
     * @var callable[]
     */
    private $binds = array();

    /**
     * Boots the service provider.
     */
    public function boot()
    {

    }

    /**
     * Registers the bindings in the service provider.
     */
    public function register()
    {

    }

    /**
     * Gets a list of all class names that the service provider can provide.
     *
     * @return string[]
     */
    public function provides()
    {
        return array();
    }

    /**
     * Binds a class name to a callable which can provide instances of that class name. The callable is expected to
     * return either a single instance or an array of instances.
     *
     * @param string $className
     * @param callable $callable
     */
    protected function bind($className, $callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Service providers must bind class names to callables.');
        }

        $this->binds[$className] = $callable;
    }

    /**
     * Resolves a class name and returns an array of objects.
     *
     * @param string $className
     * @return object[]
     */
    public function resolve($className)
    {
        if (!$this->registered) {
            $this->register();
            $this->registered = true;
        }

        if (!array_key_exists($className, $this->binds)) {
            return array();
        }

        $callable = $this->binds[$className];
        $response = call_user_func($callable);

        if (is_array($response)) {
            return $response;
        }
        else if (is_object($response) && $response instanceof $className) {
            return array($response);
        }

        return array();
    }

    /**
     * Gets whether the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this->defer;
    }

}
