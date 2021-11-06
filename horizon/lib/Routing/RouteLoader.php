<?php

namespace Horizon\Routing;

use Horizon\Exception\HorizonException;
use Horizon\Support\Facades\Application;

class RouteLoader {

	/**
	 * @var Router
	 */
	protected static $router;

	/**
	 * @var string
	 */
	protected static $currentDirectory = null;

	/**
	 * Creates a router instance.
	 */
	protected static function createRouter() {
		static::$router = new Router();
	}

	/**
	 * Gets the router instance.
	 *
	 * @internal
	 * @return Router
	 */
	public static function getRouter() {
		if (is_null(static::$router)) {
			static::createRouter();
		}

		return static::$router;
	}

	/**
	 * Loads the specified route file.
	 *
	 * @param string $filePath
	 */
	public static function loadRouteFile($filePath, $reset = false) {
		// Store the previous directory
		$previous = static::$currentDirectory;

		// Get the directory
		$routeDirectory = dirname($filePath);
		static::$currentDirectory = $routeDirectory;

		// Check that the file exists
		if (!file_exists($filePath)) {
			throw new HorizonException(0x0005, $filePath);
		}

		// Reset if requested
		if ($reset) static::reset();

		// Execute the file
		require $filePath;

		// Reset the directory
		static::$currentDirectory = $previous;
	}

	/**
	 * Clears the router's current state.
	 *
	 * @return void
	 */
	public static function reset() {
		// Tell the router to generate a new top level group
		static::getRouter()->resetMainGroup();
	}

	/**
	 * Gets the last directory where routes were loaded. This is mainly used internally.
	 *
	 * @internal
	 * @return string
	 */
	public static function getLastDirectory() {
		if (is_null(static::$currentDirectory)) {
			return Application::path('app/routes/');
		}

		return static::$currentDirectory;
	}

}
