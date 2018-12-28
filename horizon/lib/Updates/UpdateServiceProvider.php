<?php

namespace Horizon\Updates;

use Horizon\Foundation\Framework;
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
            $repo->setChannel(Framework::edition());
            $repo->setMountPath(Framework::path('horizon/'));
            $repo->setCurrentVersion(Framework::version());

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
