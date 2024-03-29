<?php

namespace Horizon\Http;

use Horizon\Foundation\Application;

class Controller {

	/**
	 * Get the middleware assigned to the controller. This is in excess to middleware defined at the router level.
	 *
	 * @return string[]|string
	 */
	public function getMiddleware() {
		return array();
	}

	/**
	 * Gets the Request instance for this controller.
	 *
	 * @return Request
	 */
	public function getRequest() {
		return Application::kernel()->http()->request();
	}

	/**
	 * Gets the Response instance for this controller.
	 *
	 * @return Response
	 */
	public function getResponse() {
		return Application::kernel()->http()->response();
	}

}
