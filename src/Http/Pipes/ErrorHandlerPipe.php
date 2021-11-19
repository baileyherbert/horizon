<?php

namespace Horizon\Http\Pipes;

use Horizon\Exception\ErrorHandler;
use Horizon\Exception\ErrorMiddleware;
use Horizon\Http\Pipe;
use Horizon\Http\Request;
use Horizon\Http\Response;

/**
 * This default pipe implementation allows you to easily change the error handler for all matching requests.
 */
class ErrorHandlerPipe extends Pipe {

	/**
	 * The error handler to set or `null` to use the default.
	 *
	 * @var ErrorHandler|null
	 */
	public $handler;

	/**
	 * Invoked before route/controller matching.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function beforeExecute(Request $request, Response $response) {
		ErrorMiddleware::$customHandler = $this->handler;
	}

}
