<?php

namespace Horizon\Routing\Controllers;

use Horizon\Foundation\Application;
use Horizon\Http\Response;
use Horizon\Http\Controller;

class SinglePageActionController extends Controller {

	public function __invoke(Response $response, $spaFilePath) {
		$response->sendFile(Application::root($spaFilePath));
	}

}
