<?php

namespace Horizon\Foundation;

use Horizon\Foundation\Services\Configuration;
use Horizon\Support\Container\Container;
use Horizon\Support\Services\ServiceObjectCollection;
use Horizon\Support\Services\ServiceProvider;
use Horizon\Exception\HorizonException;
use Horizon\Support\Path;
use Horizon\Support\Profiler;

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
	 * @var ApplicationPathProvider
	 */
	private static $paths;

	/**
	 * @var object
	 */
	private static $composer;

	/**
	 * @var object
	 */
	private static $composerLock;

	/**
	 * Gets the decoded composer.json object for the application.
	 *
	 * @return object
	 */
	public static function composer() {
		if (is_null(static::$composer)) {
			$path = static::root('composer.json');
			static::$composer = json_decode(file_get_contents($path));
		}

		return static::$composer;
	}

	/**
	 * Gets the decoded composer.lock object for the application.
	 *
	 * @return object
	 */
	public static function lock() {
		if (is_null(static::$composerLock)) {
			if (is_file($path = Path::resolve(static::paths()->vendor(), '../composer.lock'))) {
				static::$composerLock = json_decode(file_get_contents($path));
			}
		}

		return static::$composerLock;
	}

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
		Profiler::recordAsset('Service providers', get_class($provider), function() use ($provider) {
			static::container()->register($provider);
		});
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
	public static function root($relative = '') {
		return static::paths()->root($relative);
	}

	/**
	 * Returns a path provider instance that can be used to get absolute paths to various parts of the application.
	 *
	 * @return ApplicationPathProvider
	 */
	public static function paths() {
		if (is_null(static::$paths)) {
			static::$paths = new ApplicationPathProvider();
		}

		return static::$paths;
	}

	/**
	 * Returns a path to an asset in the `public` folder intended for use in link, script, and image references on
	 * the outputted pages.
	 *
	 * @param string $relativePath
	 * @return string
	 */
	public static function asset($relativePath = '') {
		$relativePath = trim($relativePath, '/');
		$root = trim($_SERVER['SUBDIRECTORY'], '/');

		if (Application::routing() === 'legacy') {
			$uri = trim(config('app.paths.assets_legacy', ''), '/');
			$path = Path::join('/', $root, $uri, $relativePath);
			$path = str_replace('\\', '/', $path);

			return $path;
		}

		$uri = trim(config('app.paths.assets', ''), '/');
		$path = Path::join('/', $root, $uri, $relativePath);
		$path = str_replace('\\', '/', $path);

		return $path;
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
		return env('APP_MODE', 'development');
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
