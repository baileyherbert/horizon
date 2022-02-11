<?php

namespace Horizon\Routing\Controllers;

use Horizon\Foundation\Application;
use Horizon\Http\Response;
use Horizon\Http\Controller;
use Horizon\Http\Request;
use Horizon\Support\Path;
use Horizon\Support\Web\WebRequest;
use InvalidArgumentException;
use UnexpectedValueException;
use Horizon\Support\Web\WebRequestException;

class SinglePageActionController extends Controller {

	public function __invoke(Request $request, Response $response, $spaFilePath, $suffix = '/') {
		$devServerPort = $request->route()->getOption('devServerPort');

		if (is_mode('development') && is_int($devServerPort)) {
			if ($this->fetchDevServer($request, $response, $devServerPort, $suffix)) {
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

		$value = $this->injectBaseDir($value);
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
	private function fetchDevServer(Request $request, Response $response, $port, $suffix) {
		if ($this->testDevServerConnection($port)) {
			$baseDir = $request->route()->getOption('baseDir', '/');
			$baseDir = '/' . ltrim($baseDir, '/');
			$requestUri = '/' . ltrim($suffix, '/');

			if (!starts_with($requestUri, $baseDir)) {
				$requestUri = '/' . trim($baseDir, '/') . $requestUri;
			}

			$url = "http://127.0.0.1:{$port}" . $requestUri;

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
				$content = $httpResponse->getBody();
				$contentType = $httpResponse->getHeader('content-type') ?: '';

				if (starts_with($contentType, 'text/html', true)) {
					$content = $this->injectBaseDir($content);
				}

				$response->write($content);

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

	/**
	 * Injects a script with the basedir into the content.
	 *
	 * @param string $content
	 * @return string
	 */
	private function injectBaseDir($content) {
		$request = request();
		$uri = $request->route()->uri();
		$uri = substr($uri, 0, strpos($uri, '{'));

		$baseDir = Path::getRelative($request->path(), $uri, $_SERVER['SUBDIRECTORY']);
		$snippet = sprintf('<script>window.baseDir="%s"</script>', $baseDir);
		return preg_replace('/^([ \t]*)(<script)/mi', "$1$snippet\n$1$2", $content);
	}

}
