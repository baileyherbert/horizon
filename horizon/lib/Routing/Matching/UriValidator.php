<?php

namespace Horizon\Routing\Matching;

use Horizon\Routing\Route;
use Horizon\Http\Request;

class UriValidator
{

	/**
	 * Validates that the route's uri matches the request.
	 *
	 * @param Route $route
	 * @param Request $request
	 * @return bool
	 */
	public function matches(Route $route, Request $request)
	{
		if (USE_LEGACY_ROUTING && $request->isLegacyRoutingAllowed()) {
			return $this->legacy($route, $request);
		}

		return preg_match($route->compile()->getRegex(), rawurldecode($request->path()));
	}

	/**
	 * Validates that the route's legacy path matches the request.
	 *
	 * @param Route $route
	 * @param Request $request
	 * @return bool
	 */
	protected function legacy(Route $route, Request $request)
	{
		$path = $request->path();
		$routePath = $route->fallback();

		$requiredVariables = $this->getRequiredParameters($route);

		if (!is_null($routePath)) {
			if ($path == $routePath) {

				foreach ($requiredVariables as $variableName) {
					if (!$request->query($variableName)) {
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the required parameters from the request uri.
	 *
	 * @param Route $route
	 * @return array
	 */
	protected function getRequiredParameters($route)
	{
		preg_match_all('/\{(\w+?)\}/', $route->uri(), $matches);

		return array_keys(isset($matches[1]) ? array_fill_keys($matches[1], null) : array());
	}

}
