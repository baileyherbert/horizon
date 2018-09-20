<?php

namespace Horizon\Routing;

use Closure;

use Horizon\Http\Request;

use Horizon\Routing\Route;
use Horizon\Routing\RouteGroup;

use Horizon\Routing\Controllers\ViewActionController;
use Horizon\Routing\Controllers\RedirectActionController;

use Horizon\Exception\HttpResponseException;

class Router
{

    /**
     * @var Route[]
     */
    protected $routes = array();

    /**
     * @var RouteGroup[]
     */
    protected $groups = array();

    /**
     * @var RouteGroup
     */
    protected $currentGroup = null;

    /**
     * @var string[]
     */
    public static $verbs = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');

    /**
     * Constructs a new Router instance.
     */
    public function __construct()
    {
        $this->resetMainGroup();
    }

    /**
     * Creates a Route instance and adds it to the router.
     *
     * @param string|string[] $methods
     * @param string $uri
     * @param Closure|null $action
     * @return Route
     */
    private function addRoute($methods, $uri, $action)
    {
        if (!is_array($methods)) {
            $methods = array($methods);
        }

        foreach ($methods as $i => $method) {
            $methods[$i] = strtoupper($method);
        }

        $route = new Route($methods, $this->applyGroupProperty('prefix', $uri), $action, $this->currentGroup);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Creates a RouteGroup instance, adds it to the router, and executes its closure.
     *
     * @param Closure|null $callback
     * @param array $properties
     * @return RouteGroup
     */
    private function addRouteGroup(Closure $callback, array $properties = array())
    {
        // Create the group
        $group = new RouteGroup($properties, $this->currentGroup);

        // Push to the groups array
        $this->groups[] = $group;

        // Execute the callback closure
        $this->executeGroup($group, $callback);

        // Return the new group
        return $group;
    }

    /**
     * Registers a new GET route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createGetRoute($uri, $action)
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Registers a new HEAD route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createHeadRoute($uri, $action)
    {
        return $this->addRoute('HEAD', $uri, $action);
    }

    /**
     * Registers a new POST route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createPostRoute($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Registers a new PUT route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createPutRoute($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Registers a new PATCH route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createPatchRoute($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Registers a new DELETE route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createDeleteRoute($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Registers a new OPTIONS route with the router.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createOptionsRoute($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Registers a new ANY route with the router, which applies to all methods.
     *
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createAnyRoute($uri, $action)
    {
        return $this->addRoute(static::$verbs, $uri, $action);
    }

    /**
     * Registers a route with the router which matches multiple methods as specified in the first parameter.
     *
     * @param string[] $methods
     * @param string $uri
     * @param Closure|array|string $action
     * @return Route
     */
    public function createMatchRoute(array $methods, $uri, $action)
    {
        return $this->addRoute($methods, $uri, $action);
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
    public function createViewRoute($uri, $view, array $variables = array())
    {
        return $this->addRoute(static::$verbs, $uri, get_class(new ViewActionController()))
               ->defaults('view', $view)
               ->defaults('variables', $variables);
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
    public function createRedirectRoute($uri, $to, $code = 302)
    {
        return $this->addRoute(static::$verbs, $uri, get_class(new RedirectActionController()))
               ->defaults('to', $to)
               ->defaults('code', $code);
    }

    /**
     * Registers a basic route group which acts as a container to isolate middleware and constraints.
     *
     * @param array|Closure $propertiesOrCallback
     * @param Closure $callback
     * @return RouteGroup
     */
    public function createGroup($propertiesOrCallback = null, $callback = null)
    {
        if (is_null($callback)) {
            $callback = $propertiesOrCallback;
            $propertiesOrCallback = array();
        }

        return $this->addRouteGroup($callback, $propertiesOrCallback);
    }

    /**
     * Registers a prefix to use for subsequent route registrations. If the second, optional $callback parameter
     * is supplied, creates a route group instead.
     *
     * @param string $prefix
     * @param Closure|null $callback
     * @return RouteGroup|void
     */
    public function createPrefix($prefix, Closure $callback = null)
    {
        if (!is_null($callback)) {
            return $this->createPrefixGroup($prefix, $callback);
        }

        $this->currentGroup->setPrefix($prefix);
    }

    /**
     * Registers a route group with a URI prefix.
     *
     * @param string $prefix
     * @param Closure $callback
     * @return RouteGroup
     */
    public function createPrefixGroup($prefix, Closure $callback)
    {
        return $this->addRouteGroup($callback, array(
            'prefix' => $prefix
        ));
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
    public function createMiddleware($middleware, Closure $callback = null)
    {
        if (!is_array($middleware)) {
            $middleware = array($middleware);
        }

        if (!is_null($callback)) {
            return $this->createMiddlewareGroup($middleware, $callback);
        }

        $this->currentGroup->addMiddleware($middleware);
    }

    /**
     * Registers a middleware group which applies to all routes registered within that closure.
     *
     * @param string|string[] $middleware
     * @param Closure $callback
     * @return RouteGroup
     */
    public function createMiddlewareGroup($middleware, Closure $callback)
    {
        if (!is_array($middleware)) {
            $middleware = array($middleware);
        }

        return $this->addRouteGroup($callback, array(
            'middleware' => $middleware
        ));
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
    public function createName($name, Closure $callback = null)
    {
        if (!is_null($callback)) {
            return $this->createNameGroup($name, $callback);
        }

        $this->currentGroup->setName($name);
    }

    /**
     * Registers a name prefix group which applies to all routes registered within the closure.
     *
     * @param string $name
     * @param Closure $callback
     * @return RouteGroup
     */
    public function createNameGroup($name, Closure $callback)
    {
        return $this->addRouteGroup($callback, array(
            'name' => $name
        ));
    }

    /**
     * Registers a namespace. Subsequent route registrations will have their controllers prefixed with
     * the provided namespace when being required. If the second $callback parameter is supplied, creates a group
     * instead.
     *
     * @param string $namespace
     * @param Closure|null $callback
     * @return RouteGroup|void
     */
    public function createNamespace($namespace, Closure $callback = null)
    {
        if (!is_null($callback)) {
            return $this->createNamespaceGroup($namespace, $callback);
        }

        $this->currentGroup->setNamespace($namespace);
    }

    /**
     * Registers a namespace group. Routes registered within this group will have their controllers prefixed with
     * the provided namespace when being required.
     *
     * @param string $namespace
     * @param Closure $callback
     * @return RouteGroup
     */
    public function createNamespaceGroup($namespace, Closure $callback)
    {
        return $this->addRouteGroup($callback, array(
            'namespace' => $namespace . '\\'
        ));
    }

    /**
     * Registers a domain. Routes registered after this declaration will only apply if the current request domain
     * matches the specified domain. The domain supports curly braces to define a variable, for example
     * "{subdomain}.example.com".
     *
     * If the second $callback parameter is provided, it creates a group instead (alias of createDomainGroup).
     *
     * @param string $domain
     * @param Closure $callback
     * @return RouteGroup
     */
    public function createDomain($domain, Closure $callback = null)
    {
        if (!is_null($callback)) {
            return $this->createDomainGroup($namespace, $callback);
        }

        $this->currentGroup->setDomain($domain);
    }

    /**
     * Registers a domain group. Routes registered within this group will only apply if the current request domain
     * matches the specified domain.
     *
     * @param string $domain
     * @param Closure $callback
     * @return RouteGroup
     */
    public function createDomainGroup($domain, Closure $callback)
    {
        return $this->addRouteGroup($callback, array(
            'domain' => $domain
        ));
    }

    /**
     * Finds a route matching the provided request. If no such route is found, returns null.
     *
     * @return Route|null
     */
    public function match(Request $request)
    {
        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Executes the group callback.
     *
     * @param RouteGroup $group
     * @param Closure $callback
     */
    private function executeGroup(RouteGroup $group, Closure $callback)
    {
        // Get the current group
        $originalGroup = $this->currentGroup;

        // Set the current group to the new group
        $this->currentGroup = $group;

        // Execute closure
        $callback();

        // Restore the original group
        $this->currentGroup = $originalGroup;
    }

    /**
     * Applies a property from the current group tree to the provided value.
     *
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    private function applyGroupProperty($property, $value)
    {
        // Skip if there is no current group
        if (!$this->currentGroup) {
            return $value;
        }

        // Execute the property method in the group
        return $this->currentGroup->$property($value);
    }

    /**
     * Creates a new main group instance.
     */
    public function resetMainGroup()
    {
        $this->currentGroup = new RouteGroup(array());
    }

}