<?php

namespace Horizon\Extension;

use Horizon;
use Horizon\Support\Path;
use Horizon\Framework\Kernel;
use Horizon\Framework\Application;

use DirectoryIterator;

trait ExtensionKernel
{

    /**
     * @var Extension[]
     */
    private static $extensions = array();

    /**
     * @var string[] Key is the namespace, and value is the source directory.
     */
    private static $extensionNamespaces = array();

    /**
     * Loads extensions from the app's extensions directory, if it exists, and starts the autoloader.
     */
    protected static function loadExtensions()
    {
        foreach (Application::resolve('Horizon\Extension\Extension') as $extension) {
            static::addExtension($extension);
        }
    }

    /**
     * Adds an extension to the kernel.
     *
     * @param Extension $extension
     */
    protected static function addExtension(Extension $extension)
    {
        static::$extensions[] = $extension;

        if ($extension->hasNamespace()) {
            $namespace = rtrim($extension->getNamespace(), '\\') . '\\';
            $source = $extension->getSourceDirectory();

            static::$extensionNamespaces[$namespace] = $source;
        }
    }

    /**
     * Gets all extensions loaded into the system.
     *
     * @return Extension[]
     */
    public static function getExtensions()
    {
        return static::$extensions;
    }

    /**
     * Gets all namespaces mapped by extensions.
     *
     * @return array
     */
    public static function getExtensionNamespaces()
    {
        return static::$extensionNamespaces;
    }

    /**
     * Loads the vendor autoloaders for packaged composers in extensions.
     */
    protected static function loadExtensionVendors()
    {
        foreach (static::getExtensions() as $extension) {
            if ($extension->hasAutoLoader()) {
                $path = Path::join($extension->getComposerVendorPath(), 'autoload.php');

                if (file_exists($path)) {
                    require $path;
                }
            }
        }
    }

}
