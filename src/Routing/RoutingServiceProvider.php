<?php

namespace Horizon\Routing;

use Horizon\Foundation\Application;
use Horizon\Support\Services\ServiceProvider;

/**
 * Provides routes for the request to be matched against.
 */
class RoutingServiceProvider extends ServiceProvider {

	public function register() {
		$this->bind('Horizon\Routing\RouteFile', function() {
			return new RouteFile(Application::paths()->routesDir('web.php'));
		});
	}

	public function provides() {
		return array(
			'Horizon\Routing\RouteFile'
		);
	}

}
