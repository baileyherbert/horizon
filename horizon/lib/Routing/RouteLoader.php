<?php

namespace Horizon\Routing;

use Horizon;
use Horizon\Exception\HorizonException;

class RouteLoader
{

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
    protected static function createRouter()
    {
        static::$router = new Router();
    }

    /**
     * Gets the router instance.
     *
     * @internal
     * @return Router
     */
    public static function getRouter()
    {
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
    public static function loadRouteFile($filePath)
    {
        // Get the directory
        $routeDirectory = dirname($filePath);
        static::$currentDirectory = $routeDirectory;

        // Check that the file exists
        if (!file_exists($filePath)) {
            throw new HorizonException(0x0005, $filePath);
        }

        // Tell the router to generate a new top level group
        static::getRouter()->resetMainGroup();

        // Execute the file
        require $filePath;
    }

    /**
     * Gets the last directory where routes were loaded. This is mainly used internally.
     *
     * @internal
     * @return string
     */
    public static function getLastDirectory()
    {
        return static::$currentDirectory;
    }

}
