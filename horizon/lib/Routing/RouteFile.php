<?php

namespace Horizon\Routing;

use Horizon\Support\Profiler;

/**
 *
 */
class RouteFile
{

	/**
	 * Absolute path to the route file.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * RouteFile constructor.
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}

	/**
	 * Checks if the file exists.
	 *
	 * @return bool
	 */
	public function exists()
	{
		return is_string($this->path) && file_exists($this->path);
	}

	/**
	 * Loads the route file into the framework.
	 */
	public function load()
	{
		if ($this->exists()) {
			Profiler::start('router:require');
			require $this->path;
			Profiler::stop('router:require');
		}
	}

}
