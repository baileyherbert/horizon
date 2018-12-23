<?php

namespace Horizon\Updates;

use Horizon\Framework\Core;
use Horizon\Support\Services\ServiceProvider;

/**
 *
 */
class UpdateServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->bind('Horizon\Updates\Repository', function() {
            $repo = new Repository('https://updates.bailey.sh/horizon');
            $repo->setChannel(Core::edition());
            $repo->setMountPath(Core::path('horizon/'));
            $repo->setCurrentVersion(Core::version());

            return $repo;
        });
    }

    public function provides()
    {
        return array(
            'Horizon\Updates\Repository'
        );
    }

}
