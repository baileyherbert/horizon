<?php

namespace Horizon\Http;

use Horizon\Http\Request;
use Horizon\Http\Response;

abstract class Middleware {

	/**
	 * The methods that this middleware will be invoked for. Defaults to all methods.
	 *
	 * @var string[]
	 */
	public $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

	/**
	 * Invoked before the controller for the current request is executed.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function beforeExecute(Request $request, Response $response) {

	}

	/**
	 * Invoked after the controller for the current request has finished executing successfully.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function afterExecute(Request $request, Response $response) {

	}

}
