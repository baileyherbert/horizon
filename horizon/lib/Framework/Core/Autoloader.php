<?php

namespace Horizon\Framework\Core;

use Horizon\Framework\Application;
use Horizon\Support\Path;

/**
 * Utility class which sets up core autoloading for the application and framework.
 */
class Autoloader
{

    /**
     * @var array<string, string>
     */
    private static $map = array();

    /**
     * @var bool
     */
    private static $started = false;

    /**
     * Mounts a namespace to the given path. The path should either be absolute or relative to the application's root
     * directory.
     *
     * @param string $namespace
     * @param string $path
     */
    public static function mount($namespace, $path)
    {
        // Get namespaces from configuration
        foreach (config('namespaces.map') as $namespace => $relativePath) {
            $namespace = trim($namespace, '\\') . '\\';
            $absolutePath = Path::resolve(Application::path(), ltrim($relativePath, '/'));

            static::$map[$namespace] = $absolutePath;
        }

        if (!static::$started) {
            static::start();
        }
    }

    /**
     * Includes a composer vendor file to be autoloaded.
     *
     * @param string $path
     */
    public static function vendor($path)
    {
        if (file_exists($path)) {
            require $path;
        }
    }

    /**
     * Starts the SPL autoloader.
     */
    private static function start()
    {
        static::$started = true;

        spl_autoload_register(function($className) {
            $className = ltrim($className, '\\');

            foreach (static::$map as $prefix => $mount) {
                $len = strlen($prefix);

                if (strncmp($prefix, $className, $len) !== 0) {
                    continue;
                }

                $relativeClass = substr($className, $len);
                $file = Path::join($mount, str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php');

                if (file_exists($file)) {
                    require $file;
                }
            }
        });
    }

}
