<?php

namespace Horizon\Routing;

use Horizon\Foundation\Application;
use Horizon\Support\Profiler;

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
        Profiler::start('router:boot');
        $routeFiles = Application::collect('Horizon\Routing\RouteFile');

        foreach ($routeFiles as $file) {
            $file->load();
        }
        Profiler::stop('router:boot');
    }

}
