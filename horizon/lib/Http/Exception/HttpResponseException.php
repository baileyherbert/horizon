<?php

namespace Horizon\Http\Exception;

class HttpResponseException extends \Exception {

	public function __construct($code, $message = null) {
		parent::__construct($message, $code);
	}

}
