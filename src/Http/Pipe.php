<?php

namespace Horizon\Http;

class Pipe {

	/**
	 * The methods that this pipe will be invoked for. Defaults to all methods.
	 *
	 * @var string[]
	 */
	public $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

	/**
	 * Invoked before route/controller matching.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function beforeExecute(Request $request, Response $response) {

	}

	/**
	 * Invoked after route/controller matching.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function afterExecute(Request $request, Response $response) {

	}

}
