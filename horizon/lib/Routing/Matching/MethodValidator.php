<?php

namespace Horizon\Routing\Matching;

use Horizon\Routing\Route;
use Horizon\Http\Request;

class MethodValidator
{

    /**
     * Validates that the route's methods match the request.
     *
     * @param Route $route
     * @param Request $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        $method = strtoupper($request->getMethod());

        return in_array($method, $route->methods());
    }

}