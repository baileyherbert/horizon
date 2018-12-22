<?php

namespace Horizon\View;

use Horizon\Extension\Extension;
use Horizon\Support\Path;

/**
 *
 */
class ViewLoader
{

    /**
     * The root path for the file loader.
     *
     * @var string
     */
    protected $path;

    /**
     * The extension this loader is loading from, or null.
     *
     * @var Extension|null
     */
    protected $extension;

    /**
     * Constructs a new ViewLoader in the specified directory path and under the specified extension.
     *
     * @param string $path
     * @param Extension|null $extension
     */
    public function __construct($path, $extension = null)
    {
        $this->path = $path;
    }

    /**
     * Resolves a view file given its relative name and returns the absolute path if it exists. Otherwise, returns null.
     *
     * @param string $viewFileName
     * @return string|null
     */
    public function resolve($viewFileName)
    {
        // Resolve the exact file name
        if (file_exists($path = Path::resolve($this->path, $viewFileName))) {
            return $path;
        }

        // Resolve the file name with ".twig" suffixed
        if (file_exists($path = Path::resolve($this->path, $viewFileName . '.twig'))) {
            return $path;
        }

        // No match
        return null;
    }

    /**
     * Gets the extension this loader is loading from, or null.
     *
     * @return Extension|null
     */
    public function getExtension()
    {
        return $this->extension;
    }

}
