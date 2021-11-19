<?php

namespace Horizon\Http\Pipes;

use Horizon\Http\Pipe;
use Horizon\Http\Request;
use Horizon\Http\Response;

/**
 * This default pipe implementation allows you to quickly add headers to all matching requests.
 */
class HeaderPipe extends Pipe {

	/**
	 * An array of headers to set.
	 *
	 * @var string[]
	 */
	public $headers = [];

	/**
	 * Invoked before route/controller matching.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function beforeExecute(Request $request, Response $response) {
		foreach ($this->headers as $header) {
			list($name, $value) = explode(':', $header, 2);
			$response->setHeader(trim($name), trim($value));
		}
	}

}
