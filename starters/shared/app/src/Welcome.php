<?php

namespace App;

use Horizon\Http\Controller;
use Horizon\Http\Request;
use Horizon\Http\Response;

class Welcome extends Controller {

	public function get(Request $request, Response $response) {
		view('start');
	}

}
