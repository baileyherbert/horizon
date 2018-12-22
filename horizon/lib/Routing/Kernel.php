<?php

namespace Horizon\Routing;

use Horizon\Framework\Application;

/**
 * Kernel for routing.
 */
class Kernel
{

    /**
     * Loads route files from service providers and executes them.
     */
    public function boot()
    {
        $routeFiles = Application::resolve('Horizon\Routing\RouteFile');

        foreach ($routeFiles as $file) {
            $file->load();
        }
    }

}
