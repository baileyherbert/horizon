<?php

namespace Horizon\Http\Middleware;

use Horizon\Http\Middleware;
use Horizon\Http\Request;
use Horizon\Http\Cookie\TokenMismatchException;
use Horizon\Http\Response;

class TokenMiddleware extends Middleware {

	public $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

	public function beforeExecute(Request $request, Response $response) {
		$token = $request->session()->csrf();
		$posted = $this->getPostedToken();

		if ($posted !== $token) {
			throw new TokenMismatchException();
		}
	}

	/**
	 * Gets the token sent by the user in the current request, checking both posted input and headers.
	 *
	 * @return string|null
	 */
	private function getPostedToken() {
		$post = request()->input('_token');
		$header = request()->header('x-csrf-token');

		if (!is_null($post)) return $post;
		return $header;
	}

}
