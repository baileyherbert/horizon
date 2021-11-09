<?php

namespace Horizon\Routing\Matching;

use Horizon\Routing\Route;
use Horizon\Http\Request;

interface ValidatorInterface {

	/**
	 * Validates that the route matches the request.
	 *
	 * @param Route $route
	 * @param Request $request
	 * @return bool
	 */
	public function matches(Route $route, Request $request);

}
