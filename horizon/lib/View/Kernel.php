<?php

namespace Horizon\View;

use Horizon\Extension\Extension;
use Horizon\Framework\Application;

/**
 * Kernel for views.
 */
class Kernel
{

    /**
     * The extension from which the last-resolved template was loaded.
     *
     * @var Extension|null
     */
    private $extension = null;

    /**
     * @var ViewLoader[]
     */
    private $loaders = array();

    /**
     * Boots the kernel.
     */
    public function boot()
    {
        $this->loaders = Application::resolve('Horizon\View\ViewLoader');
    }

    /**
     * Resolves a template name to an absolute path, or returns null if it wasn't found.
     *
     * @param string $templateName
     * @return string|null
     */
    public function resolve($templateName)
    {
        $path = null;

        // Reset the extension binding
        $this->extension = null;

        foreach ($this->loaders as $loader) {
            if (!is_null($path = $loader->resolve($templateName))) {
                $this->extension = $loader->getExtension();
            }
        }

        return $path;
    }

    /**
     * Gets the extension responsible for the last resolution.
     *
     * @return Extension|null
     */
    public function extension()
    {
        return $this->extension;
    }

}
