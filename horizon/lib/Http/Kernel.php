<?php

namespace Horizon\Http;

use Exception;

use Horizon\Exception\ErrorMiddleware;
use Horizon\Exception\HorizonException;
use Horizon\Foundation\Framework;
use Horizon\Http\Exception\HttpResponseException;
use Horizon\Routing\Route;
use Horizon\Support\Container\BoundCallable;
use Horizon\Support\Path;
use Horizon\Support\Profiler;
use Horizon\Foundation\Application;
use Horizon\Console\ConsoleResponse;
use Horizon\Routing\ExceptionHandlerDispatcher;
use Horizon\Routing\RouteLoader;
use Horizon\Support\Str;

/**
 * Kernel for HTTP, controllers, middleware, and everything in between.
 */
class Kernel
{

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
    public function boot()
    {
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
    public function execute($callback = null)
    {
        Profiler::start('kernel:http');

        // Find a matching route
        $route = $this->route = $this->match();

        // If the route was found, execute it
        if ($route) {
            // Run callback
            if (is_callable($callback)) {
                call_user_func($callback, $route);
            }

            try {
                // Run middleware
                Profiler::start('http:middleware');
                $this->executeMiddleware($route);
                Profiler::stop('http:middleware');

                // Run the controller
                Profiler::start('http:controller');
                $this->executeController($route);
                Profiler::stop('http:controller');
            }
            catch (Exception $e) {
                $this->handleException($e, $route);
            }
        }

        // Close
        Profiler::stop('kernel:http');
        $this->close();
    }

    /**
     * Gets the Request instance for the current request. This will be null if running in console mode.
     *
     * @return Request|null
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Gets the Response instance for the current request. If called from a console environment, the returned response
     * object will act as a middleware to the console's current output object.
     *
     * @return Response
     */
    public function response()
    {
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
    public function route()
    {
        return $this->route;
    }

    /**
     * Closes the HTTP kernel and sends the output body and headers.
     *
     * @param bool $skipErrorPage
     */
    public function close($skipErrorPage = false)
    {
        $this->response->halt();
        $this->response->prepare($this->request);
        $this->response->send();

        if (!$this->response->getContent() && $this->response->getStatusCode() != 200 && !$skipErrorPage) {
            $this->error($this->response->getStatusCode());
        }

        // Stop profiling
        Profiler::stop('kernel');

        // Stop the kernel
        Application::kernel()->shutdown();
    }

    /**
     * Renders an error page.
     *
     * @param int $code
     */
    public function error($code)
    {
        $errorFilePaths = array(
            Application::path('app/errors/' . $code . '.html'),
            Application::path('horizon/errors/' . $code . '.html')
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

        $this->response->setStatusCode($code);
        $this->close(true);
    }

    /**
     * Looks for a matching route and stores it in the kernel. This must be called before you can execute the kernel
     * and run the route. Returns a route on success, null if not found, and false if a redirection took place.
     *
     * @return Route|null|false
     */
    private function match()
    {
        Profiler::start('router:match');
        $route = RouteLoader::getRouter()->match($this->request);

        // Show a 404 if not found
        if (is_null($route)) {
            if (!$this->tryDirectoryRedirect()) {
                $this->handleException(new HttpResponseException(404));
                return null;
            }

            return false;
        }

        // Bind the route to the request
        $this->request->bind($route);
        Profiler::stop('router:match');
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
     * @throws HorizonException Middleware could not be found.
     * @throws Exception Failed to bind contextual parameters.
     */
    private function executeMiddleware(Route $route)
    {
        $middlewares = $route->middleware();

        foreach ($middlewares as $middleware) {
            $action = Str::parseCallback($middleware, '__invoke');
            $className = head($action);

            if (class_exists($className)) {
                $callable = new BoundCallable($action, Application::container());

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
            else {
                throw new HorizonException(0x0006, sprintf('Middleware (%s)', $className));
            }

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
    private function executeController(Route $route)
    {
        if ($this->response->isHalted()) {
            return;
        }

        // Execute the controller
        $route->execute($this->request, $this->response);
    }

    /**
     * Sets the X-Powered-By header with a credit to Horizon and its current version. Can be toggled off via the
     * app.expose_horizon config option.
     */
    private function sendExposedHeader()
    {
        $framework = 'Horizon';

        if (config('app.expose_php', true)) $framework .= ' / PHP ' . phpversion();
        if (config('app.expose_horizon', true) === false) header_remove('X-Powered-By');
        else if (!headers_sent()) header('X-Powered-By: ' . $framework);
    }

    /**
     * Detects if the application is running in a subdirectory and saves relevant information.
     */
    private function detectSubdirectory()
    {
        $rootPath = Application::path();
        $requestUri = $_SERVER['REQUEST_URI'];
        $queryString = '';

        if (strpos($requestUri, '?') !== false) {
            $queryString = substr($requestUri, strpos($requestUri, '?'));
            $requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
        }

        if (strpos($rootPath, '/horizon/bootstrap/') !== false) {
            $rootPath = substr($rootPath, 0, strpos($rootPath, '/horizon/bootstrap/'));
        }

        $root = Path::parse(str_replace('\\', '/', $rootPath));
        $uri = Path::parse($requestUri);
        $shifted = '';

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

        $newRequestUri = $requestUri;
        $newRequestUri = substr($newRequestUri, strlen($shifted));

        if (USE_LEGACY_ROUTING) {
            $nodes = Path::parse($newRequestUri);

            if (empty($nodes) || $nodes[count($nodes) - 1]->directory) {
                $newRequestUri .= 'index.php';
            }
        }

        $_SERVER['REQUEST_URI'] = $newRequestUri . $queryString;
    }

    /**
     * Creates the Request instance.
     */
    private function createRequest()
    {
        $this->request = Request::auto();
    }

    /**
     * Creates the Response instance.
     */
    private function createResponse()
    {
        $this->response = Application::environment() != 'console' ? new Response() : new ConsoleResponse();
    }

    /**
     * Tries to redirect from a file to a directory. For example, if the current request is to /about and no route is
     * found at that location, it will try /about/. This emulates web servers like Apache and nginx. Returns true if
     * a redirection has taken place.
     *
     * @return bool
     */
    private function tryDirectoryRedirect()
    {
        if (config('app.redirect_to_directories', true) === false) {
            return false;
        }

        if (substr($this->request->path(), -1) !== "/") {
            // Add a trailing slash to the request uri
            $_SERVER['REQUEST_URI'] = $this->request->path() . '/';

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
            if (!is_null($route)) {
                $this->response->redirect($this->request->fullUrl(), 301);
                $this->close();

                return true;
            }
        }

        return false;
    }

}
