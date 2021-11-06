<?php

namespace Horizon\Foundation;

use Horizon\Foundation\Services\Configuration;
use Horizon\Support\Container\Container;
use Horizon\Support\Path;
use Horizon\Support\Services\ServiceObjectCollection;
use Horizon\Support\Services\ServiceProvider;
use Horizon\Exception\HorizonException;

/**
 * The base application class.
 * @internal
 */
class Application {

	/**
	 * @var Kernel
	 */
	private static $kernel;

	/**
	 * The primary service container for the application.
	 *
	 * @var Container
	 */
	private static $container;

	/**
	 * Gets the service container for the application.
	 *
	 * @return Container
	 */
	public static function container() {
		if (is_null(static::$container)) {
			static::$container = new Container();
		}

		return static::$container;
	}

	/**
	 * Registers a service provider in the application.
	 *
	 * @param ServiceProvider $provider
	 * @return void
	 */
	public static function register(ServiceProvider $provider) {
		static::container()->register($provider);
	}

	/**
	 * Boots the service providers registered to the application.
	 */
	public static function boot() {
		static::container()->boot();
	}

	/**
	 * Returns a collection of service objects derived from the given class name.
	 *
	 * @param string $className
	 * @return ServiceObjectCollection
	 */
	public static function collect($className) {
		return static::container()->all($className);
	}

	/**
	 * Returns a single instance of the requested class name from the service container.
	 *
	 * @param string $className
	 * @return object|null
	 */
	public static function make($className) {
		return static::container()->make($className);
	}

	/**
	 * Returns an absolute path to the application's root directory. If a relative path is specified, the returned path
	 * will be an absolute path to the specified location within the application.
	 *
	 * @param string $relative
	 * @return string
	 */
	public static function path($relative = '') {
		$basedir = dirname(dirname(dirname(__DIR__)));
		$relative = ltrim($relative, '\\/');

		return Path::join($basedir, $relative);
	}

	/**
	 * Returns a path to an asset in the `app/public` folder intended for use in link, script, and image references on
	 * the outputted pages.
	 *
	 * @param string $relative
	 * @return string
	 */
	public static function asset($relative = '') {
		$root = rtrim(self::basedir(), '/');

		if (Application::routing() === 'legacy') {
			return $root . '/app/public/' . ltrim($relative, '/');
		}

		return $root . '/assets/' . ltrim($relative, '/');
	}

	/**
	 * Returns the current environment (`web`, `test`, or `console`).
	 *
	 * @return string
	 */
	public static function environment() {
		return env('HORIZON_MODE');
	}

	/**
	 * Returns the current environment mode (`production`, `development`, or `staging`).
	 *
	 * @return string
	 */
	public static function mode() {
		return env('APP_MODE');
	}

	/**
	 * Gets the current routing mode (legacy, rewrite, none).
	 *
	 * @return string
	 */
	public static function routing() {
		return env('ROUTING_MODE', 'router');
	}

	/**
	 * Returns the base directory of the application relative to the website's document root.
	 * For example, if the application is installed in a subdirectory, the base directory will be the full path of that
	 * subdirectory.
	 *
	 * This always begins with a '/'.
	 *
	 * @return string
	 */
	public static function basedir() {
		$directory = trim($_SERVER['SUBDIRECTORY'], '/');
		$directory = str_replace('\\', '/', $directory);

		return '/' . $directory;
	}

	/**
	 * Gets the current version of the application.
	 *
	 * @return string
	 * @throws HorizonException
	 */
	public static function version() {
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
	public static function config($key, $default = null) {
		return Configuration::get($key, $default);
	}

	/**
	 * Gets the primary kernel for the application and framework.
	 *
	 * @return Kernel
	 */
	public static function kernel() {
		return static::$kernel ?: (static::$kernel = new Kernel());
	}

}
