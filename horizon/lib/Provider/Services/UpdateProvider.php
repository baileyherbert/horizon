<?php

namespace Horizon\Provider\Services;

use Horizon;
use Horizon\Provider\ServiceProvider;
use Horizon\Updates\Repository;

class UpdateProvider extends ServiceProvider
{

    /**
     * Returns an array of update repositories.
     *
     * @return Repository[]
     */
    public function __invoke()
    {
        $repo = new Repository('https://updates.bailey.sh/horizon');
        $repo->setChannel(Horizon::EDITION);
        $repo->setMountPath(Horizon::HORIZON_DIR);
        $repo->setCurrentVersion(Horizon::VERSION);

        return array($repo);
    }

}
