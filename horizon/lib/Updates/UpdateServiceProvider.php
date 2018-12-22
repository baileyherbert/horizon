<?php

namespace Horizon\Updates;

use Horizon;
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
            $repo->setChannel(Horizon::EDITION);
            $repo->setMountPath(Horizon::HORIZON_DIR);
            $repo->setCurrentVersion(Horizon::VERSION);

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
