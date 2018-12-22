<?php

namespace Horizon\Http;

use Horizon\Routing\RouteLoader;

use Exception;
use Horizon;
use Horizon\Exception\HorizonException;
use Horizon\Http\Exception\HttpResponseException;
use Horizon\Support\Profiler;
use Horizon\Support\Path;
use Horizon\Support\Arr;
use Horizon\Exception\ErrorMiddleware;
use Horizon\Console\ConsoleResponse;

trait HttpKernel
{

    /**
     * @var Request
     */
    protected static $request;

    /**
     * @var Response
     */
    protected static $response;

    /**
     * @var ConsoleResponse
     */
    protected static $consoleResponse;

    /**
     * @var string
     */
    private static $realPath;

    /**
     * Create the request instance.
     */
    protected static function makeRequest()
    {
        static::$request = Request::auto();
    }

    /**
     * Gets the request instance.
     *
     * @return Request
     */
    public static function getRequest()
    {
        if (defined('CONSOLE_MODE')) {
            throw new Exception('Cannot retrieve HTTP Request instance in console mode.');
        }

        return static::$request;
    }

    /**
     * Create the response instance.
     */
    protected static function makeResponse()
    {
        if (is_null(static::$response)) {
            static::$response = new Response();
        }
    }

    /**
     * Gets the response instance.
     *
     * @return Response
     */
    public static function getResponse()
    {
        if (defined('CONSOLE_MODE')) {
            if (!isset(static::$consoleResponse)) {
                static::$consoleResponse = new ConsoleResponse();
            }

            return static::$consoleResponse;
        }

        return static::$response;
    }

    /**
     * Sets, changes, or removes the X-Powered-By header per the 'app' configuration.
     */
    protected static function prepareHttp()
    {
        $framework = 'Horizon ' . Horizon::VERSION;

        if (config('app.expose_php', true)) {
            $framework .= ' / PHP ' . FRAMEWORK_PHP_VERSION;
        }

        if (config('app.expose_horizon', true) === false) {
            header_remove('X-Powered-By');
        }
        else if (!headers_sent()) {
            header('X-Powered-By: ' . $framework);
        }
    }

    /**
     * Checks if the framework is installed to a subdirectory and alters the REQUEST_URI to exclude it.
     */
    protected static function prepareSubdirectory()
    {
        $rootPath = FRAMEWORK_HORIZON_ROOT;
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
                if (!empty($root) && Arr::last($root)->name == $node->name) {
                    $shifted = '/' . $node->name . $shifted;
					array_pop($root);
                }
            }
        }

        $_SERVER['SUBDIRECTORY'] = trim($shifted, '/');

        $newRequestUri = $requestUri;
        $newRequestUri = substr($newRequestUri, strlen($shifted));

        if (USE_LEGACY_ROUTING) {
            $nodes = Path::parse($newRequestUri);

            if (empty($nodes) || Arr::last($nodes)->directory) {
                $newRequestUri .= 'index.php';
            }
        }

        $_SERVER['REQUEST_URI'] = $newRequestUri . $queryString;
    }

    /**
     * Matches the request to a route.
     */
    protected static function match()
    {
        static::makeResponse();
        $route = RouteLoader::getRouter()->match(static::$request);

        // Show a 404 if not found
        if (is_null($route)) {
            if (!static::testDirectoryRedirect()) {
                return ErrorMiddleware::getErrorHandler()->http(new HttpResponseException(404, 'router'));
            }

            return;
        }

        // Bind the route to the request
        static::$request->bind($route);

        // Record the stage for debugging errors
        $stage = 'middleware';

        // Execute the page
        try {
            static::loadLanguages();
            static::executeBootstrapper();
            static::executeMiddleware();

            $stage = 'controller';
            static::executeRequest();

            $stage = 'output';
            static::output();
        }
        catch (HttpResponseException $e) {
            ErrorMiddleware::getErrorHandler()->http($e);
        }
    }

    /**
     * Redirect to a matching directory route if one exists.
     */
    protected static function testDirectoryRedirect()
    {
        if (config('app.redirect_to_directories') === false) {
            return false;
        }

        if (substr(static::$request->path(), -1) !== "/") {
            // Add a trailing slash to the request uri
            $_SERVER['REQUEST_URI'] = static::$request->path() . '/';

            // Add query string
            if (static::$request->getQueryString()) {
                $_SERVER['REQUEST_URI'] .= '?' . static::$request->getQueryString();
            }

            // Store the path for errors
            static::$realPath = static::$request->path();

            // Create a new request object
            static::makeRequest();

            // See if the directory route matches
            $route = RouteLoader::getRouter()->match(static::$request);

            // Redirect to the new uri
            if (!is_null($route)) {
                static::makeResponse();
                static::$response->redirect(static::$request->fullUrl(), 301);
                static::output();

                return true;
            }
        }

        return false;
    }

    /**
     * Runs bootstrap scripts with a priority value of 3.
     *
     * @return void
     */
    protected static function executeBootstrapper()
    {
        static::runBootScripts(3);
    }

    /**
     * Executes middleware.
     */
    protected static function executeMiddleware()
    {
        $middlewares = static::$request->getRoute()->middleware();

        try {
            foreach ($middlewares as $className) {
                if (class_exists($className)) {
                    $middleware = new $className;
                    $middleware(static::$request, static::$response);
                }
                else {
                    throw new HorizonException(0x0006, sprintf('Middleware (%s)', $className));
                }

                if (static::$response->isHalted()) {
                    break;
                }
            }
        }
        catch (HttpResponseException $e) {
            ErrorMiddleware::getErrorHandler()->http($e);
        }

    }

    /**
     * Dispatches the controller for the current request.
     */
    protected static function executeRequest()
    {
        if (static::$response->isHalted()) {
            return;
        }

        // Execute the controller
        static::$request->getRoute()->execute(static::$request, static::$response);
    }

    /**
     * Renders output to the page.
     */
    protected static function output($skipErrorPage = false)
    {
        static::$response->halt();
        static::$response->prepare(static::$request);
        static::$response->send();

        if (!static::$response->getContent() && static::$response->getStatusCode() != 200 && !$skipErrorPage) {
            static::showErrorPage(static::$response->getStatusCode());
        }

        Profiler::stop('kernel');

        static::close();
    }

    /**
     * Renders an error page.
     *
     * @param int $code
     */
    public static function showErrorPage($code)
    {
        $errorFilePaths = array(
            Horizon::APP_DIR . SLASH . 'errors' . SLASH . $code . '.html',
            Horizon::HORIZON_DIR . SLASH . 'errors' . SLASH . $code . '.html'
        );

        foreach ($errorFilePaths as $path) {
            if (file_exists($path)) {
                if (is_null(static::$response)) {
                    static::makeResponse();
                }

                $requestPath = (isset(static::$realPath)) ? static::$realPath : static::$request->path();

                $contents = file_get_contents($path);
                $contents = str_replace('{{ path }}', $requestPath, $contents);

                static::$response->setContent($contents);

                break;
            }
        }

        static::$response->setStatusCode($code);
        static::output(true);
    }

}
