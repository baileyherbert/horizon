<?php

namespace Horizon\Routing;

use Horizon\Http\Request;

class RouteParameterBinder
{

    /**
     * @var Route
     */
    protected $route;

    /**
     * Constructs a new RouteParameterBinder instance.
     *
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function bind(Request $request)
    {
        $parameters = $this->bindPathParameters($request);

        if (!is_null($this->route->compile()->getHostRegex())) {
            $parameters = $this->bindHostParameters(
                $request, $parameters
            );
        }

        return $this->replaceDefaults($parameters);
    }

    /**
     * Extract the parameter list from the path part of the request.
     *
     * @param Request $request
     * @return array
     */
    public function bindPathParameters(Request $request)
    {
        $path = $request->path();

        preg_match($this->route->compile()->getRegex(), $path, $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }

    /**
     * Extract the parameter list from the host part of the request.
     *
     * @param Request $request
     * @param array $parameters
     * @return array
     */
    protected function bindHostParameters($request, $parameters)
    {
        preg_match($this->route->compile()->getHostRegex(), $request->getHost(), $matches);
        return array_merge($this->matchToKeys(array_slice($matches, 1)), $parameters);
    }

    /**
     * Combine a set of parameter matches with the route's keys.
     *
     * @param array $matches
     * @return array
     */
    protected function matchToKeys(array $matches)
    {
        $parameterNames = $this->route->parameterNames();

        if (empty($parameterNames)) {
            return array();
        }

        $parameters = array_intersect_key($matches, array_flip($parameterNames));

        return array_filter($parameters, function ($value) {
            return is_string($value) && strlen($value) > 0;
        });
    }

    /**
     * Replace null parameters with their defaults.
     *
     * @param array $parameters
     * @return array
     */
    protected function replaceDefaults(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (!$parameters[$key]) {
                if (isset($this->route->defaults[$key])) {
                    $parameters[$key] = $this->route->defaults[$key];
                }
            }
        }

        foreach ($this->route->defaults as $key => $value) {
            if (!isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

}