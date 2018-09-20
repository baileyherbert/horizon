<?php

namespace Horizon\Provider\Services;

use Horizon;
use Horizon\Utils\Path;
use Horizon\Provider\ServiceProvider;

class RoutingProvider extends ServiceProvider
{

    /**
     * Returns an array of absolute paths to isolated route files for loading.
     *
     * @return string[]
     */
    public function __invoke()
    {
        $routePath = Path::join(Horizon::APP_DIR, 'routes', 'web.php');

        if (file_exists($routePath)) {
            return array($routePath);
        }
    }

}
