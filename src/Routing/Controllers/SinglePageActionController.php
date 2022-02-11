<?php

namespace Horizon\Routing\Controllers;

use Horizon\Foundation\Application;
use Horizon\Http\Response;
use Horizon\Http\Controller;
use Horizon\Http\Request;
use Horizon\Support\Web\WebRequest;
use InvalidArgumentException;
use UnexpectedValueException;
use Horizon\Support\Web\WebRequestException;

class SinglePageActionController extends Controller {

	public function __invoke(Request $request, Response $response, $spaFilePath) {
		$devServerPort = $request->route()->getOption('devServerPort');

		if (is_mode('development') && is_int($devServerPort)) {
			if ($this->fetchDevServer($request, $response, $devServerPort)) {
				return;
			}
		}

		$spaFilePath = Application::root($spaFilePath);
		if (!file_exists($spaFilePath)) {
			$response->write("SPA: File not found: $spaFilePath");
			return;
		}

		$value = file_get_contents($spaFilePath);
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

	/**
	 * Fetches content from the development server. Returns `true` if successful.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param mixed $port
	 * @return bool
	 * @throws InvalidArgumentException
	 * @throws UnexpectedValueException
	 * @throws WebRequestException
	 */
	private function fetchDevServer(Request $request, Response $response, $port) {
		if ($this->testDevServerConnection($port)) {
			$url = "http://127.0.0.1:{$port}" . $request->getRequestUri();

			$http = new WebRequest($url);
			$http->setTimeout(2);

			$disallowedHeaders = ['if-none-match'];
			foreach ($request->headers->all() as $header => $value) {
				if (!in_array(strtolower($header), $disallowedHeaders)) {
					$http->setHeader($header, $value[0]);
				}
			}

			$httpResponse = $http->get();
			$httpResponseCode = $httpResponse->getStatusCode();

			if (in_array($httpResponseCode, [200, 304])) {
				$response->write($httpResponse->getBody());

				$disallowedHeaders = ['etag'];
				foreach ($httpResponse->getHeaders() as $header => $value) {
					if (!in_array(strtolower($header), $disallowedHeaders)) {
						$response->setHeader($header, $value);
					}
				}

				return true;
			}

			$response->write("SPA: Got status code $httpResponseCode from the development server (port $port).");
			return true;
		}

		return false;
	}

	/**
	 * Tests whether we can connect to the devserver.
	 *
	 * @param int $port
	 * @return bool
	 */
	private function testDevServerConnection($port) {
		$connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);

		if (is_resource($connection)) {
			fclose($connection);
			return true;
		}

		return false;
	}

}
