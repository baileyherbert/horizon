<?php

namespace Horizon\Routing;

use Horizon\Utils\Str;
use Horizon\Utils\Path;
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
     * @return Route
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
     * @return Route
     */
    public static function get($uri, $action)
    {
        return static::router()->createGetRoute($uri, $action);
    }

    /**
     * Registers a new POST route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public static function post($uri, $action)
    {
        return static::router()->createPostRoute($uri, $action);
    }

    /**
     * Registers a new PUT route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public static function put($uri, $action)
    {
        return static::router()->createPutRoute($uri, $action);
    }

    /**
     * Registers a new PATCH route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public static function patch($uri, $action)
    {
        return static::router()->createPatchRoute($uri, $action);
    }

    /**
     * Registers a new DELETE route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public static function delete($uri, $action)
    {
        return static::router()->createDeleteRoute($uri, $action);
    }

    /**
     * Registers a new OPTIONS route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public static function options($uri, $action)
    {
        return static::router()->createOptionsRoute($uri, $action);
    }

    /**
     * Registers a new route with the router which applies to all methods.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public static function any($uri, $action)
    {
        return static::router()->createAnyRoute($uri, $action);
    }

    /**
     * Registers a new route with the router which applies to the provided methods.
     *
     * @param string[] $methods
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public static function match(array $methods, $uri, $action)
    {
        return static::router()->createMatchRoute($uri, $action);
    }

    /**
     * Registers a route with a default controller which renders the specified view. An array of variables can be sent
     * to the view through the third parameter.
     *
     * @param string $uri
     * @param string $view
     * @param array $variables
     * @return Route
     */
    public static function view($uri, $view, array $variables = array())
    {
        return static::router()->createViewRoute($uri, $view, $variables);
    }

    /**
     * Registers a new redirection route with the router, which applies to all methods. The $to parameter can
     * contain an absolute or relative link, or another route, including its object, name, or URI (with variables filled).
     * The default redirection code is 302 and can be overridden with the third argument.
     *
     * @param string $uri
     * @param Route|string $to
     * @param int $code
     * @return Route
     */
    public static function redirect($uri, $to, $code = 302)
    {
        return static::router()->createRedirectRoute($uri, $to, $code);
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
     * Loads another route file with the given name. The name must not end in '.php' and will be loaded relative to
     * the app/routes directory.
     *
     * @param string $fileName
     */
    public static function load($fileName)
    {
        if (!Str::endsWith($fileName, '.php')) {
            $fileName .= '.php';
        }

        RouteLoader::loadRouteFile(Path::join(RouteLoader::getLastDirectory(), $fileName));
    }

}
