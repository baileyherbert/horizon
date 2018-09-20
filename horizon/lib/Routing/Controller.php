<?php

namespace Horizon\Routing;

class Controller
{

    /**
     * Get the middleware assigned to the controller. This is in excess to middleware defined at the router level.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return array();
    }

}