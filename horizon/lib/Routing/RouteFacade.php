<?php

namespace Horizon\Routing;

use Horizon\Support\Str;
use Horizon\Support\Path;
use Closure;

/**
 * Interface class for creating routes globally.
 */
class RouteFacade
{

    /**
     * Gets the router instance.
     *
     * @internal
     * @return Router
     */
    protected static function router()
    {
        return RouteLoader::getRouter();
    }

    /**
     * Registers a new GET route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function get($uri, $action = null, $fallback = null)
    {
        if (is_null($action)) {
            $action = $uri;
            $uri = '';
        }

        $route = static::router()->createGetRoute($uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new POST route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function post($uri, $action = null, $fallback = null)
    {
        if (is_null($action)) {
            $action = $uri;
            $uri = '';
        }

        $route = static::router()->createPostRoute($uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new PUT route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function put($uri, $action = null, $fallback = null)
    {
        if (is_null($action)) {
            $action = $uri;
            $uri = '';
        }

        $route = static::router()->createPutRoute($uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new PATCH route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function patch($uri, $action = null, $fallback = null)
    {
        if (is_null($action)) {
            $action = $uri;
            $uri = '';
        }

        $route = static::router()->createPatchRoute($uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new DELETE route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function delete($uri, $action = null, $fallback = null)
    {
        if (is_null($action)) {
            $action = $uri;
            $uri = '';
        }

        $route = static::router()->createDeleteRoute($uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new OPTIONS route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function options($uri, $action = null, $fallback = null)
    {
        if (is_null($action)) {
            $action = $uri;
            $uri = '';
        }

        $route = static::router()->createOptionsRoute($uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new route with the router which applies to all methods.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function any($uri, $action = null, $fallback = null)
    {
        if (is_null($action)) {
            $action = $uri;
            $uri = '';
        }

        $route = static::router()->createAnyRoute($uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new route with the router which applies to the provided methods.
     *
     * @param string[] $methods
     * @param string $uri
     * @param Closure|array|string $action
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function match(array $methods, $uri, $action, $fallback = null)
    {
        $route = static::router()->createMatchRoute($methods, $uri, $action);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a route with a default controller which renders the specified view. An array of variables can be sent
     * to the view through the third parameter.
     *
     * @param string $uri
     * @param string $view
     * @param array $variables
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function view($uri, $view, array $variables = array(), $fallback = null)
    {
        $route = static::router()->createViewRoute($uri, $view, $variables);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new redirection route with the router, which applies to all methods. The $to parameter can
     * contain an absolute or relative link, or another route, including its object, name, or URI (with variables filled).
     * The default redirection code is 302 and can be overridden with the third argument.
     *
     * @param string $uri
     * @param Route|string $to
     * @param int $code
     * @param string|null $fallback Path to a php file, relative to the root directory, to use for fallback routing.
     * @return Route
     */
    public static function redirect($uri, $to, $code = 302, $fallback = null)
    {
        $route = static::router()->createRedirectRoute($uri, $to, $code);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a new TCP proxy route with the router. All traffic to this route will be forwarded to the specified
     * address and port as an HTTP connection, with all request information sent as originally received locally.
     * If the timeout reaches before the connection completes, the user will receive a 504 Gateway Timeout error.
     *
     * Data received from the remote server will be forwarded to the response, including any headers or status codes.
     *
     * The $address will be formatted to HTTP if not done already. If it already has query arguments, they will be
     * appended to if the current request has query arguments, overwriting as necessary.
     *
     * For HTTPS addresses, a valid SSL certificate is required on the remote server, and the default CA bundle will be
     * applied if $caBundle is not specified.
     *
     * @param string $uri
     * @param string $address Hostname, URL, or IP address.
     * @param int $port Port number of the remote server (HTTP = 80, HTTPS = 443, custom is allowed).
     * @param string|null $caBundle Path relative to the framework's root directory to a CA bundle for SSL peer validation.
     * @param int $timeout Number of seconds to wait before timing out the request.
     * @return Route
     */
    public static function tcp($uri, $address, $port = 80, $timeout = 30, $caBundle = null, $fallback = null)
    {
        $route = static::router()->createTcpProxyRoute($uri, $address, $port, $caBundle, $timeout);

        if (!is_null($fallback)) {
            $route->fallback($fallback);
        }

        return $route;
    }

    /**
     * Registers a basic route group which acts as a container to isolate middleware and constraints.
     *
     * @param array|Closure $propertiesOrCallback
     * @param Closure $callback
     * @return RouteGroup
     */
    public static function group($propertiesOrCallback = null, $callback = null)
    {
        return static::router()->createGroup($propertiesOrCallback, $callback);
    }

    /**
     * Registers a prefix to use for subsequent route registrations. If the second, optional $callback parameter
     * is supplied, creates a route group instead.
     *
     * @param string $prefix
     * @param Closure|null $callback
     * @return RouteGroup|void
     */
    public static function prefix($prefix, Closure $callback = null)
    {
        return static::router()->createPrefix($prefix, $callback);
    }

    /**
     * Registers a middleware. If the second parameter is provided a closure, it creates a middleware group which applies
     * to all routes registered within that closure. Otherwise, the middleware applies to all routes defined thereafter,
     * in the current scope.
     *
     * @param string|string[] $middleware
     * @param Closure|null $callback
     * @return RouteGroup|void
     */
    public static function middleware($middleware, Closure $callback = null)
    {
        return static::router()->createMiddleware($middleware, $callback);
    }

    /**
     * Registers a name prefix. If the second parameter is provided a closure, it creates a name group which applies
     * to all routes registered within that closure. Otherwise, the prefix applies to all routes defined thereafter,
     * in the current scope.
     *
     * @param string $name
     * @param Closure|null $callback
     * @return RouteGroup|void
     */
    public static function name($name, Closure $callback = null)
    {
        return static::router()->createName($name, $callback);
    }

    /**
     * Registers a domain. Routes registered after this declaration will only apply if the current request domain
     * matches the specified domain. The domain supports curly braces to define a variable, for example
     * "{subdomain}.example.com".
     *
     * If the second $callback parameter is provided, it creates a group instead (alias of createDomainGroup).
     *
     * @param string $domain
     * @param Closure|null $callback
     * @return RouteGroup
     */
    public static function domain($domain, Closure $callback = null)
    {
        return static::router()->createDomain($domain, $callback);
    }

    /**
     * Registers a handler for uncaught exceptions in the current scope. This can be used to intercept HTTP errors
     * and any other exceptions thrown from controllers. The specified action will receive the exception in its
     * parameters, and can also receive instances like the `Request` or `Response`.
     *
     * @param Closure|array|string $action
     * @return Route
     */
    public static function catch($action)
    {
        return static::router()->setExceptionHandler($action);
    }

    /**
     * Loads another route file with the given name. The name must not end in '.php' and will be loaded relative to
     * the app/routes directory.
     *
     * @param string $fileName
     * @param bool $reset Enable to clear all previous middleware and groups before loading the file.
     */
    public static function load($fileName, $reset = false)
    {
        if (!Str::endsWith($fileName, '.php')) {
            $fileName .= '.php';
        }

        RouteLoader::loadRouteFile(Path::join(RouteLoader::getLastDirectory(), $fileName), $reset);
    }

    /**
     * Clears all middleware, groups, prefixes, and settings for new rules created after calling this method.
     *
     * @return void
     */
    public static function reset()
    {
        RouteLoader::reset();
    }

}
