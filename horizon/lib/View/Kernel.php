<?php

namespace Horizon\View;

use Horizon\Foundation\Application;

/**
 * Kernel for views.
 */
class Kernel
{

    /**
     * @var ViewLoader[]
     */
    private $loaders = array();

    /**
     * Boots the kernel.
     */
    public function boot()
    {
        $this->loaders = Application::collect('Horizon\View\ViewLoader');
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

        foreach ($this->loaders as $loader) {
            $absolute = $loader->resolve($templateName);

            if (!is_null($absolute)) {
                $path = $absolute;
            }
        }

        return $path;
    }

}
