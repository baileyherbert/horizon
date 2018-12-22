<?php

namespace Horizon\View;

use Horizon\Framework\Application;
use Horizon\Support\Services\ServiceProvider;

/**
 * Provides view mounts that can be used to load view files.
 */
class ViewServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->bind('Horizon\View\ViewLoader', function() {
            return new ViewLoader(Application::path('app/views'));
        });
    }

    public function provides()
    {
        return array(
            'Horizon\View\ViewLoader'
        );
    }

}
