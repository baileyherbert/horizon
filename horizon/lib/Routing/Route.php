<?php

namespace Horizon\Routing;

use Closure;
use Horizon\Support\Str;
use Symfony\Component\Routing\CompiledRoute;
use Horizon\Http\Request;
use Horizon\Http\Exception\HttpResponseException;
use Horizon\Routing\Matching\ValidatorInterface;
use Horizon\Routing\Matching\MethodValidator;
use Horizon\Routing\Matching\DomainValidator;
use Horizon\Routing\Matching\UriValidator;
use Horizon\Http\Response;

class Route
{

    /**
     * @var ValidatorInterface[]
     */
    private static $validators;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var array
     */
    protected $methods;

    /**
     * @var Closure
     */
    protected $action;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var RouteGroup
     */
    protected $group = null;

    /**
     * @var array
     */
    public $defaults = array();

    /**
     * @var array
     */
    public $wheres = array();

    /**
     * @var CompiledRoute
     */
    protected $compiled;

    /**
     * @var string|null
     */
    protected $fallback;

    /**
     * @var array|null
     */
    protected $parameters;

    /**
     * Creates a new route instance.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  Closure|array  $action
     * @return void
     */
    public function __construct($methods, $uri, $action, RouteGroup $group = null)
    {
        // Ensure methods is an array
        if (!is_array($methods)) {
            $methods = array($methods);
        }

        // Set properties
        $this->methods = $methods;
        $this->uri = $uri;
        $this->action = RouteAction::parse($action);
        $this->group = $group;

        // Get domain from group
        if (!is_null($group)) {
            $this->domain = $this->group->domain();
        }
    }

    /**
     * Gets the URI for the route, including any prefixes from parent route groups.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Alias of getUri().
     *
     * @return string
     */
    public function uri()
    {
        return $this->getUri();
    }

    /**
     * Gets the methods for the route as an array.
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Alias of getMethods().
     *
     * @return string[]
     */
    public function methods()
    {
        return $this->getMethods();
    }

    /**
     * Gets the action in the form of a callable.
     *
     * @return callable
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Returns the controller instance for this route if it has one.
     *
     * @return Controller|null
     */
    public function getControllerInstance()
    {
        $action = $this->getAction();

        if (is_string($action)) {
            $callback = Str::parseCallback($action);

            if (is_array($callback) && class_exists($callback[0])) {
                return new $callback[0];
            }
        }

        if (is_array($action) && isset($action[0]) && is_string($action[0])) {
            if (class_exists($action[0])) {
                return new $action[0];
            }
        }

        return null;
    }

    /**
     * Alias of getMethods().
     *
     * @return callable
     */
    public function action()
    {
        return $this->getAction();
    }

    /**
     * Gets the route group.
     *
     * @return RouteGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Alias of getGroup().
     *
     * @return RouteGroup
     */
    public function group()
    {
        return $this->getGroup();
    }

    /**
     * Gets the route domain or null if not set.
     *
     * @return string
     */
    public function getDomain()
    {
        $domain = $this->group->domain();

        if (empty($domain)) return null;
        return $domain;
    }

    /**
     * Alias of getDomain().
     *
     * @return RouteGroup
     */
    public function domain()
    {
        return $this->getDomain();
    }

    /**
     * Stores a default value in the route, typically used by controllers to load values from the route configuration.
     *
     * @param string $key
     * @param mixed $value
     * @return Route $this
     */
    public function defaults($key, $value)
    {
        $this->defaults[$key] = $value;
        return $this;
    }

    /**
     * Gets a default value in the route. Second parameter defines what to return if the key doesn't exist.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getDefault($key, $default = null)
    {
        if (isset($this->defaults[$key])) {
            return $this->defaults[$key];
        }

        return $default;
    }

    /**
     * If the $name parameter is provided, sets the name to the provided value. Otherwise, gets the current name.
     * Note that this name is subject to group prefixing.
     *
     * @param string|null $name
     * @return string|Route
     */
    public function name($name = null)
    {
        if (is_null($name)) {
            return $this->name;
        }

        $this->name = $this->group->namePrefix() . $name;

        return $this;
    }

    /**
     * Returns the name of the route, or `null` if one is not set.
     *
     * @return string|null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Gets an array of middleware which must run before the route action.
     *
     * @return array
     */
    public function middleware()
    {
        $middleware = $this->getGroup()->middleware();
        $controller = $this->getControllerInstance();

        if (!is_null($controller)) {
            $middleware = array_merge($middleware, (array)$controller->getMiddleware());
        }

        return array_unique($middleware);
    }

    /**
     * Runs the action and returns its response (or null).
     *
     * @param Request|null $request
     * @param Response|null $response
     *
     * @return mixed
     */
    public function execute(Request $request = null, Response $response = null)
    {
        return (new ControllerDispatcher($this, $request, $response))->dispatch();
    }

    /**
     * Compile the route into a Symfony CompiledRoute instance.
     *
     * @return CompiledRoute
     */
    public function compile()
    {
        if (!$this->compiled) {
            $this->compiled = (new RouteCompiler($this))->compile();
        }

        return $this->compiled;
    }

    /**
     * Set a regular expression requirement on the route.
     *
     * @param array|string $name
     * @param string $expression
     * @return Route $this
     */
    public function where($name, $expression = null)
    {
        $parsed = is_array($name) ? $name : array($name => $expression);

        foreach ($parsed as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    /**
     * If no arguments are provided, returns the fallback file path. If the first argument is provided a string, sets the
     * fallback path and returns $this.
     *
     * @return Route|string|null
     */
    public function fallback($path = null)
    {
        if (!is_null($path)) {
            $this->fallback = '/' . ltrim($path, '/');
            return $this;
        }

        return $this->fallback;
    }

    /**
     * Checks if the route matches the provided request.
     *
     * @param Request $request
     * @param bool $includingMethod
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compile();

        foreach ($this->getValidators() as $validator) {
            if (!$includingMethod && $validator instanceof MethodValidator) {
                continue;
            }

            if (!$validator->matches($this, $request)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Binds the route to a request, which triggers parameter binding.
     *
     * @param Request $request
     */
    public function bind(Request $request)
    {
        $this->compile();
        $this->parameters = (new RouteParameterBinder($this))->bind($request);
    }

    /**
     * Gets the value of a parameter. If the parameter is not found or null, first checks defaults, and then returns
     * value of $default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function parameter($key, $default = null)
    {
        if (is_null($this->parameters)) {
            return array();
        }

        if (!isset($this->parameters[$key])) {
            if (!isset($this->defaults[$key])) {
                return $default;
            }

            return $this->defaults[$key];
        }

        return $this->parameters[$key];
    }

    /**
     * Gets all parameters generated by the route. If no binding to a request is present, returns null.
     *
     * @return array|null
     */
    public function parameters()
    {
        if (is_null($this->parameters)) {
            return array();
        }

        return $this->parameters;
    }

    /**
     * Gets an array of the names of all parameters, both required and optional, in the route uri.
     *
     * @return string[]
     */
    public function parameterNames()
    {
        preg_match_all('/\{(.*?)\}/', ($this->getDomain() ?: '') . $this->uri(), $matches);

        return array_map(function ($m) {
            return trim($m, '?');
        }, $matches[1]);
    }

    /**
     * Gets an array of validator objects to match a route to a request.
     *
     * @return ValidatorInterface[]
     */
    public static function getValidators()
    {
        if (!isset(self::$validators)) {
            self::$validators = array(
                new MethodValidator,
                new DomainValidator,
                new UriValidator
            );
        }

        return self::$validators;
    }

}
