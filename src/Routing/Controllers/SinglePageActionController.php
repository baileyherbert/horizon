<?php

namespace Horizon\Routing\Controllers;

use Horizon\Foundation\Application;
use Horizon\Http\Response;
use Horizon\Http\Controller;
use Horizon\Http\Request;

class SinglePageActionController extends Controller {

	public function __invoke(Request $request, Response $response, $spaFilePath) {
		$value = file_get_contents(Application::root($spaFilePath));
		$plainRules = $request->route()->getOption('rewrite', []);
		$regexRules = $request->route()->getOption('rewrite.regex', []);

		foreach ($plainRules as $rule => $replacement) {
			$value = str_replace($rule, $replacement, $value);
		}

		foreach ($regexRules as $expression => $replacement) {
			$value = preg_replace($expression, $replacement, $value);
		}

		$response->write($value);
	}

}
