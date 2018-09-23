<?php

namespace Horizon\View\Twig;

use Horizon;
use Horizon\Utils\Path;
use Horizon\Framework\Kernel;

class TwigExtensionLoader
{

    /**
     * @var TwigFileLoader
     */
    protected $loader;

    /**
     * Constructs a new TwigExtensionLoader instance.
     *
     * @param TwigFileLoader $loader
     */
    public function __construct(TwigFileLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Loads all extensions in the application and returns an array of instances.
     *
     * @return \Twig_Extension[]
     */
    public function getExtensions()
    {
        $extensions = array();

        foreach ($this->getExtensionDirectories() as $rootNamespace => $dir) {
            $extensions = array_merge($extensions, $this->fetchExtensionDirectory($rootNamespace, $dir));
        }

        return $extensions;
    }

    /**
     * Loads applicable extensions from the specified namespace and directory, returning an array of extension
     * instances.
     *
     * @return \Twig_Extension[]
     */
    public function fetchExtensionDirectory($namespace, $dir)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return array();
        }

        $files = scandir($dir);
        $extensions = array();

        foreach ($files as $file) {
            $path = Path::join($dir, $file);
            $className = $namespace . '\\' . Path::basename($file, '.php');

            if ($file == '.' || $file == '..') {
                continue;
            }

            if (is_dir($file)) {
                $recursive = $this->fetchExtensionDirectory(($namespace . '\\' . $file), $path);

                foreach ($recursive as $extension) {
                    $extensions[] = $extension;
                }
            }
            else {
                if (class_exists($className)) {
                    $extension = new $className($this->loader);

                    if ($extension instanceof \Twig_Extension) {
                        $extensions[] = $extension;
                    }
                }
            }
        }

        return $extensions;
    }

    /**
     * Gets an array of directories and namespaces which should contain extensions. This does not check existence,
     * and may return paths that are missing or point to files.
     *
     * @return array
     */
    public function getExtensionDirectories()
    {
        return array(
            'App\View\Extensions' => Path::join(Horizon::APP_SRC_DIR, 'View', 'Extensions'),
            'Horizon\View\Extensions' => Path::join(Horizon::HORIZON_LIB_DIR, 'View', 'Extensions')
        );
    }

}