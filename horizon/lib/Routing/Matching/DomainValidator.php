<?php

namespace Horizon\Routing\Matching;

use Horizon\Routing\Route;
use Horizon\Http\Request;

class DomainValidator
{

    /**
     * Validates that the route's domain matches the request.
     *
     * @param Route $route
     * @param Request $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        if (is_null($route->compile()->getHostRegex())) {
            return true;
        }

        return preg_match($route->compile()->getHostRegex(), $request->getHost());
    }

}