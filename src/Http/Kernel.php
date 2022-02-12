<?php

namespace Horizon\Http;

use Exception;

use Horizon\Exception\ErrorMiddleware;
use Horizon\Exception\HorizonException;
use Horizon\Http\Exception\HttpResponseException;
use Horizon\Routing\Route;
use Horizon\Support\Container\BoundCallable;
use Horizon\Support\Path;
use Horizon\Foundation\Application;
use Horizon\Console\ConsoleResponse;
use Horizon\Foundation\Framework;
use Horizon\Routing\ExceptionHandlerDispatcher;
use Horizon\Routing\RouteLoader;
use Horizon\Support\Profiler;
use Horizon\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Kernel for HTTP, controllers, middleware, and everything in between.
 */
class Kernel {

	/**
	 * @var string
	 */
	private $subdirectory;

	/**
	 * @var string
	 */
	private $realPath;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Response
	 */
	private $response;

	/**
	 * @var Route
	 */
	private $route;

	/**
	 * Boots the HTTP kernel.
	 */
	public function boot() {
		Profiler::record('Boot http kernel');

		$this->sendExposedHeader();
		$this->detectSubdirectory();
		$this->createRequest();
		$this->createResponse();
	}

	/**
	 * Executes the middleware and controller for the current matched route. The callback, which is optional, will be
	 * called once the route has been matched. The route is passed as the first and only argument.
	 *
	 * @param callable $callback
	 * @throws HorizonException
	 */
	public function execute($callback = null) {
		// Find a matching route
		$this->executePipes('before');
		$route = $this->route = $this->match();
		$this->executePipes('after');

		// If the route was found, execute it
		if ($route) {
			// Run callback
			if (is_callable($callback)) {
				call_user_func($callback, $route);
			}

			try {
				// Run middleware (beforeExecute)
				$this->executeMiddleware($route, 'before');

				// Run the controller
				$this->executeController($route);

				// Run middleware (afterExecute)
				$this->executeMiddleware($route, 'after');
			}
			catch (Exception $e) {
				$this->handleException($e, $route);
			}
		}

		// Close
		$this->close();
	}

	/**
	 * Gets the Request instance for the current request. This will be null if running in console mode.
	 *
	 * @return Request|null
	 */
	public function request() {
		return $this->request;
	}

	/**
	 * Gets the Response instance for the current request. If called from a console environment, the returned response
	 * object will act as a middleware to the console's current output object.
	 *
	 * @return Response
	 */
	public function response() {
		if (is_null($this->response)) {
			$this->createResponse();
		}

		return $this->response;
	}

	/**
	 * Gets the Route instance for the current request.
	 *
	 * @return Route|null
	 */
	public function route() {
		return $this->route;
	}

	/**
	 * Closes the HTTP kernel and sends the output body and headers.
	 *
	 * @param bool $skipErrorPage
	 */
	public function close($skipErrorPage = false) {
		$this->response->halt();
		$this->response->prepare($this->request);
		$this->response->send();

		if (!$this->response->getContent() && $this->response->getStatusCode() != 200 && !$skipErrorPage) {
			$this->error($this->response->getStatusCode());
		}

		// Stop the kernel
		Application::kernel()->shutdown();
	}

	/**
	 * Renders an error page.
	 *
	 * @param int $code
	 * @param string|null $message
	 */
	public function error($code, $message = null) {
		if ($this->response->isJson()) {
			if ($this->response->getLength() === 0) {
				$this->response->setContent(json_encode(
					[
						'status' => $code,
						'error' => SymfonyResponse::$statusTexts[$code],
						'message' => empty($message) ? 'No message available' : $message
					],
					JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
				));
			}
		}
		else {
			$errorFilePaths = array(
				Application::paths()->errors($code . '.html'),
				Framework::path('resources/errors/' . $code . '.html')
			);

			foreach ($errorFilePaths as $path) {
				if (file_exists($path)) {
					if (is_null($this->response)) {
						$this->createResponse();
					}

					$requestPath = (isset($this->realPath)) ? $this->realPath : $this->request->path();

					$contents = file_get_contents($path);
					$contents = str_replace('{{ path }}', $requestPath, $contents);

					$this->response->setContent($contents);

					break;
				}
			}
		}

		$this->response->setStatusCode($code);
		$this->close(true);
	}

	/**
	 * Executes pipes.
	 *
	 * @param string $method `before` or `after`
	 * @return void
	 */
	private function executePipes($method) {
		$requestMethod = $this->request->getMethod();

		foreach (RouteLoader::getRouter()->getPipes($this->request) as $pipe) {
			$instance = $pipe->getInstance();

			if (in_array($requestMethod, $instance->methods)) {
				Profiler::record("Execute pipe ($method): " . get_class($instance));

				$start = microtime(true);
				call_user_func([$instance, $method . 'Execute'], $this->request(), $this->response());
				Profiler::recordAsset("Pipe ($method)", get_class($instance), microtime(true) - $start);
			}
		}
	}

	/**
	 * Looks for a matching route and stores it in the kernel. This must be called before you can execute the kernel
	 * and run the route. Returns a route on success, null if not found, and false if a redirection took place.
	 *
	 * @return Route|null|false
	 */
	private function match() {
		$event = Profiler::record('Match http request to a route');
		$route = RouteLoader::getRouter()->match($this->request);

		// Show a 404 if not found
		if (is_null($route)) {
			$event->extraInformation = 'Not found';

			if (!$this->tryDirectoryRedirect()) {
				$this->handleException(new HttpResponseException(404));
				return null;
			}

			return false;
		}

		// Always try to match directories for passive routes
		else if ($route->isPassive() && $this->tryDirectoryRedirect()) {
			return false;
		}

		// Bind the route to the request
		$event->extraInformation = 'Matched: ' . $route->uri();
		$this->request->bind($route);
		return $route;
	}

	/**
	 * Handles an exception.
	 *
	 * @param Exception $ex
	 * @param Route $route
	 * @return void
	 */
	private function handleException(Exception $ex, Route $route = null) {
		$group = $route ? $route->getGroup() : RouteLoader::getRouter()->getRootGroup();
		$handler = $group->getExceptionHandler();

		// If there's no exception handler, we should pass the exception forward
		if (is_null($handler)) {
			if ($ex instanceof HttpResponseException) {
				return ErrorMiddleware::getErrorHandler()->http($ex);
			}

			throw $ex;
		}

		// Dispatch the exception handler
		try {
			$dispatcher = new ExceptionHandlerDispatcher($this->request, $this->response, $group, $ex);
			$dispatcher->dispatch();
		}
		catch (HttpResponseException $e) {
			ErrorMiddleware::getErrorHandler()->http($e);
		}
	}

	/**
	 * Executes middleware.
	 *
	 * @param Route $route
	 * @param string $method `before` or `after`
	 * @throws HorizonException Middleware could not be found.
	 * @throws Exception Failed to bind contextual parameters.
	 */
	private function executeMiddleware(Route $route, $method) {
		$middlewares = $route->middleware();
		$requestMethod = $this->request->getMethod();

		if ($this->response->isHalted()) {
			return;
		}

		foreach ($middlewares as $middleware) {
			$start = microtime(true);
			$action = Str::parseCallback($middleware, $method . 'Execute');
			$className = head($action);

			Profiler::record("Execute middleware ($method): $className");

			if (class_exists($className)) {
				$callable = new BoundCallable($action, Application::container());
				$instance = $callable->instance();

				// Is this a valid middleware?
				if ($instance instanceof Middleware) {
					if (in_array($requestMethod, $instance->methods)) {
						// Add basic objects for dependency resolution
						$callable->with($route);
						$callable->with($this->request);
						$callable->with($this->response);

						// Add attribute objects
						if (!is_null($this->request)) {
							foreach ($this->request->attributes->all() as $name => $value) {
								if (is_object($value)) {
									$callable->with($value);
								}

								$callable->where($name, $value);
							}
						}

						// Run the middleware
						$callable->execute();
					}
				}
			}
			else {
				throw new HorizonException(0x0006, sprintf('Middleware (%s)', $className));
			}

			Profiler::recordAsset("Middleware ($method)", $className, microtime(true) - $start);

			if ($this->response->isHalted()) {
				break;
			}
		}
	}

	/**
	 * Dispatches the controller for the current request.
	 *
	 * @param Route $route
	 */
	private function executeController(Route $route) {
		if ($this->response->isHalted()) {
			return;
		}

		// Execute the controller
		$result = $route->execute($this->request, $this->response);

		// Send the returned response if applicable
		if ($this->response->getLength() === 0 && !is_null($result)) {
			Profiler::record('Generate response from return value');

			$this->response->writeLine(json_encode(
				$result,
				JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
			));

			if (!$this->response->getHeader('content-type')) {
				$this->response->setHeader('content-type', 'application/json');
			}
		}
	}

	/**
	 * Sets the X-Powered-By header with a credit to Horizon and its current version. Can be toggled off via the
	 * app.expose_horizon config option.
	 */
	private function sendExposedHeader() {
		$framework = 'Horizon';

		if (config('app.expose_php', false)) $framework .= ' / PHP ' . phpversion();
		if (config('app.expose_horizon', false) === false) header_remove('X-Powered-By');
		else if (!headers_sent()) header('X-Powered-By: ' . $framework);
	}

	/**
	 * Detects if the application is running in a subdirectory and saves relevant information.
	 */
	private function detectSubdirectory() {
		$event = Profiler::record('Detect current subdirectory');

		$rootPath = Application::root();
		$requestUri = $_SERVER['REQUEST_URI'];
		$queryString = '';
		$shifted = '';

		if (is_string(env('BASEDIR'))) {
			$_SERVER['SUBDIRECTORY'] = trim(env('BASEDIR'), '/');
			$this->subdirectory = $_SERVER['SUBDIRECTORY'];

			return;
		}
		else {
			if (strpos($requestUri, '?') !== false) {
				$queryString = substr($requestUri, strpos($requestUri, '?'));
				$requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
			}

			$root = Path::parse(str_replace('\\', '/', $rootPath));
			$uri = Path::parse($requestUri);

			for ($i = count($uri) - 1; $i >= 0; $i--) {
				$node = $uri[$i];

				if ($node->directory) {
					if (!empty($root) && $root[count($root) - 1]->name == $node->name) {
						$shifted = '/' . $node->name . $shifted;
						array_pop($root);
					}
				}
			}

			$_SERVER['SUBDIRECTORY'] = trim($shifted, '/');
			$this->subdirectory = $_SERVER['SUBDIRECTORY'];
		}

		$newRequestUri = $requestUri;
		$newRequestUri = substr($newRequestUri, strlen($shifted));

		if (Application::routing() === 'legacy') {
			$nodes = Path::parse($newRequestUri);

			if (empty($nodes) || $nodes[count($nodes) - 1]->directory) {
				$newRequestUri .= 'index.php';
			}
		}

		$_SERVER['REQUEST_URI'] = $newRequestUri . $queryString;
		$event->extraInformation = $this->subdirectory ?: 'No subdirectory';
	}

	/**
	 * Creates the Request instance.
	 */
	private function createRequest() {
		Profiler::record('Create request instance');
		$this->request = Request::auto();
	}

	/**
	 * Creates the Response instance.
	 */
	private function createResponse() {
		Profiler::record('Create response instance');
		$this->response = Application::environment() != 'console' ? new Response() : new ConsoleResponse();
	}

	/**
	 * Tries to redirect from a file to a directory. For example, if the current request is to /about and no route is
	 * found at that location, it will try /about/. This emulates web servers like Apache and nginx. Returns true if
	 * a redirection has taken place.
	 *
	 * @return bool
	 */
	private function tryDirectoryRedirect() {
		if (config('app.redirect_to_directories', true) === false) {
			return false;
		}

		Profiler::record('Check for directory redirection');

		if (substr($this->request->path(), -1) !== "/") {
			$originalUri = $this->request->path();
			$originalRequest = $this->request;

			// Add a trailing slash to the request uri
			$_SERVER['REQUEST_URI'] = $originalUri . '/';

			// Add query string
			if ($this->request->getQueryString()) {
				$_SERVER['REQUEST_URI'] .= '?' . $this->request->getQueryString();
			}

			// Store the path for errors
			$this->realPath = $this->request->path();

			// Create a new request object
			$this->createRequest();

			// See if the directory route matches
			$route = RouteLoader::getRouter()->match($this->request);

			// Redirect to the new uri
			if (!is_null($route) && substr($route->uri(), -1) === '/') {
				$this->response->redirect($this->request->getRequestUri(), 302);
				$this->close();

				return true;
			}

			// Revert the request uri
			$this->realPath = $_SERVER['REQUEST_URI'] = $originalUri;
			$this->request = $originalRequest;
		}

		return false;
	}

}
