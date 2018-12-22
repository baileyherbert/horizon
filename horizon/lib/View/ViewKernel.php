<?php

namespace Horizon\View;

use Horizon\Extension\Extension;
use Horizon\Framework\Application;

trait ViewKernel
{

    private static $files = array();

    /**
     * @var Extension
     */
    private static $extension;

    /**
     * Retrieves the full path to a template file, or null if it doesn't exist.
     *
     * @param string $name
     * @return string|null
     */
    public static function getTemplatePath($name)
    {
        $path = null;

        // Reset the extension binding
        static::$extension = null;

        foreach (Application::resolve('Horizon\View\ViewLoader') as $loader) {
            if (!is_null($path = $loader->resolve($name))) {
                static::$extension = $loader->getExtension();
            }
        }

        return $path;
    }

    /**
     * Gets the extension which is currently bound to the view kernel, or null if none is bound. Typically, a bound
     * extension will be the extension who provided the last requested view file.
     *
     * @return Extension
     */
    public static function getExtensionBinding()
    {
        return static::$extension;
    }

}
