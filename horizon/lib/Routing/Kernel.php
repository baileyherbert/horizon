<?php

namespace Horizon\Routing;

use Horizon\Foundation\Application;

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
        $routeFiles = Application::collect('Horizon\Routing\RouteFile');

        foreach ($routeFiles as $file) {
            $file->load();
        }
    }

}
