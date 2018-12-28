<?php

namespace Horizon\Routing;

use Exception;
use Horizon\Foundation\Application;
use Horizon\Http\Request;
use Horizon\Http\Response;
use Horizon\Http\Exception\HttpResponseException;
use Horizon\Support\Container\BoundCallable;

class ControllerDispatcher
{

    /**
     * @var Route
     */
    private $route;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * Constructs a new ControllerDispatcher instance.
     *
     * @param Route $route
     * @param Request|null $request
     * @param Response|null $response
     */
    public function __construct(Route $route, Request $request = null, Response $response = null)
    {
        $this->route = $route;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Dispatches the controller and returns the response.
     *
     * @return mixed
     * @throws HttpResponseException
     */
    public function dispatch()
    {
        $action = $this->getCallable();

        if (is_null($this->response)) {
            $this->response = Application::kernel()->http()->response();
        }

        if (is_callable($action)) {
            $this->init($action);

            if (!$this->response->isHalted()) {
                // Create a new service-bound callable
                $callable = $this->createBoundCallable($action);

                // Run the callable
                return $callable->execute();
            }
        }

        return null;
    }

    /**
     * Runs the init() method on the controller.
     *
     * @param callable $action
     */
    private function init($action)
    {
        $action = $this->createInstance($action);

        if (is_array($action)) {
            $className = $action[0];

            if (method_exists($className, 'init')) {
                $action = array($className, 'init');

                $callable = $this->createBoundCallable($action);
                $callable->execute();
            }
        }
    }

    /**
     * Creates a service-bound callable for the given action.
     *
     * @param string|callable $action
     * @return BoundCallable
     * @throws Exception
     */
    private function createBoundCallable($action)
    {
        // Convert the action to a callable if necessary
        $action = $this->createInstance($action);

        // Create a new service-bound callable
        $callable = new BoundCallable($action, Application::container());

        // Add our basic objects for dependency resolution
        $callable->with($this->route);
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

        // Add variables from the route
        foreach ($this->route->parameterNames() as $name) {
            $value = $this->route->parameter($name);

            if (!is_null($value)) {
                $callable->where($name, $value);
            }
        }

        // Add GET variables
        if (!is_null($this->request)) {
            foreach ($this->request->query->all() as $name => $value) {
                if (!$callable->has($name)) {
                    $callable->where($name, $value);
                }
            }
        }

        return $callable;
    }

    /**
     * Gets the controller as a callable.
     *
     * @return callable
     * @throws HttpResponseException
     */
    protected function getCallable()
    {
        $action = $this->route->getAction();

        if (is_string($action) && strpos($action, '::') !== false) {
            list($className, $methodName) = explode('::', $action, 2);

            if (!class_exists($className)) {
                throw new HttpResponseException(404);
            }

            $callable = new $className();

            if (!method_exists($callable, $methodName)) {
                throw new HttpResponseException(404);
            }

            return array($callable, $methodName);
        }

        return $action;
    }

    /**
     * Creates an instance and returns the callable array to execute it.
     *
     * @param string|array $name
     * @return callable
     */
    protected function createInstance($name)
    {
        if (!is_string($name)) {
            return $name;
        }

        list($className, $method) = explode('::', $name, 2);

        return array(new $className, $method);
    }

}
