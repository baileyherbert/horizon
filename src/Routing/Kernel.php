<?php

namespace Horizon\Routing;

use Horizon\Foundation\Application;
use Horizon\Support\Profiler;

/**
 * Kernel for routing.
 */
class Kernel {

	/**
	 * Loads route files from service providers and executes them.
	 */
	public function boot() {
		Profiler::record('Boot routing kernel');

		$routeFiles = Application::collect('Horizon\Routing\RouteFile');

		foreach ($routeFiles as $file) {
			Profiler::recordAsset('Route initialization', Application::paths()->getRelative($file->path), function() use ($file) {
				$file->load();
			});
		}
	}

}
