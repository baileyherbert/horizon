<?php

namespace Horizon\Support\Container;

use Horizon\Support\Profiler;
use Horizon\Support\Services\ServiceObjectCollection;
use Horizon\Support\Services\ServiceProvider;

/**
 * A very basic service container implementation which can help resolve dependencies where necessary. It's not very
 * powerful and is only meant to be a drop-in utility where necessary.
 */
class Container {

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
	private static $booted = array();

	/**
	 * Constructs a new container instance. The constructor parameters should normally be left blank as they are used
	 * internally for cloning containers.
	 *
	 * @param ServiceProvider[] $providers
	 * @param ServiceProvider[][] $map
	 */
	public function __construct($providers = array(), $map = array()) {
		$this->providers = $providers;
		$this->providersMap = $map;
	}

	/**
	 * Registers a service provider in the container.
	 *
	 * @param ServiceProvider $provider
	 * @return void
	 */
	public function register(ServiceProvider $provider) {
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
	public function boot() {
		Profiler::record('Boot service providers');

		foreach ($this->providers as $provider) {
			$start = microtime(true);

			if ($provider->isDeferred()) continue;
			if (in_array($provider, static::$booted)) continue;

			$provider->boot();
			static::$booted[] = $provider;

			Profiler::recordAsset('Service providers', get_class($provider), microtime(true) - $start);
		}
	}

	/**
	 * Returns a collection of all service objects derived from the given class name.
	 *
	 * @param string $className
	 * @param mixed ...$args
	 * @return ServiceObjectCollection
	 */
	public function all($className, $args = null) {
		$start = microtime(true);
		$args = func_get_args();
		$collection = null;

		$className = array_shift($args);

		// Resolve from service providers
		if (array_key_exists($className, $this->providersMap)) {
			$providers = $this->providersMap[$className];
			$objects = array();

			foreach ($providers as $provider) {
				if ($provider->isDeferred() && !in_array($provider, static::$booted)) {
					$provider->boot();
				}

				foreach ($provider->resolve($className, $args) as $resolved) {
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

		Profiler::recordAsset('Container resolution', null, microtime(true) - $start);
		return $collection;
	}

	/**
	 * Returns a singleton of the given class name from the last service provider that can provide it.
	 *
	 * @param string $className
	 * @param mixed ...$args
	 * @return object|null
	 */
	public function make($className, $args = null) {
		$start = microtime(true);
		$args = func_get_args();
		$className = array_shift($args);

		if (array_key_exists($className, $this->providersMap)) {
			$providers = $this->providersMap[$className];
			$reverse = array_reverse($providers);

			foreach ($reverse as $provider) {
				if ($provider->isDeferred() && !in_array($provider, static::$booted)) {
					$provider->boot();
				}

				$resolved = $provider->resolve($className, $args);

				if (!is_null($resolved)) {
					$resolved = (array) $resolved;

					if (!empty($resolved)) {
						Profiler::recordAsset('Container resolution', null, microtime(true) - $start);
						return head($resolved);
					}
				}
			}
		}

		Profiler::recordAsset('Container resolution', null, microtime(true) - $start);
		return null;
	}

	/**
	 * Returns a new service container derived from this container. It will contain the same providers and registering
	 * new providers to the new container will not affect this one.
	 *
	 * @return Container
	 */
	public function derive() {
		return new Container($this->providers, $this->providersMap);
	}

}
