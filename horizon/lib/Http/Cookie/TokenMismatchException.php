<?php

namespace Horizon\Http\Cookie;

use Exception;
use Horizon\Http\Exception\HttpResponseException;

class TokenMismatchException extends HttpResponseException
{

	public function __construct($code = 403)
	{
		parent::__construct($code);
	}

}
