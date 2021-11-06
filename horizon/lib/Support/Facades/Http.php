<?php

namespace Horizon\Support\Facades;

/**
 * Facade for interacting with the Http kernel.
 */
class Http {

	/**
	 * Returns the Request instance for the current request. If the request has not yet started, this will return null.
	 *
	 * @return \Horizon\Http\Request|null
	 */
	public function getRequest() {
		return $this->kernel()->request();
	}

	/**
	 * Returns the Response instance for the current request. If the request has not yet started, this will return null.
	 *
	 * @return \Horizon\Http\Response|null
	 */
	public function getResponse() {
		return $this->kernel()->response();
	}

	/**
	 * Returns the Route instance for the current request. If there is no matching route, or if matching has not yet
	 * taken place, this will return null.
	 *
	 * @return \Horizon\Routing\Route|null
	 */
	public function getRoute() {
		return $this->kernel()->route();
	}

	/**
	 * Sends an error page to the output and terminates the request.
	 *
	 * @param int $code
	 * @return void
	 */
	public function showErrorPage($code = 500) {
		$this->kernel()->error($code);
	}

	/**
	 * @internal
	 * @return \Horizon\Http\Kernel
	 */
	private function kernel() {
		return \Horizon\Foundation\Application::kernel()->http();
	}

}
