<?php

namespace Horizon\Routing;

use Horizon\Framework\Application;
use Horizon\Http\Request;
use Horizon\Http\Response;
use Horizon\Http\Exception\HttpResponseException;

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
     * @var Route
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
                $parameters = $this->populateParameters($action);

                if (is_string($action)) {
                    $action = $this->createInstance($action);
                }

                return call_user_func_array($action, $parameters);
            }
        }

        return null;
    }

    private function init($action)
    {
        if (is_string($action)) {
            $action = $this->createInstance($action);
        }

        if (!is_array($action)) {
            return;
        }

        $className = $action[0];

        if (method_exists($className, 'init')) {
            $action = array($className, 'init');
            $parameters = $this->populateParameters($action);

            return call_user_func_array($action, $parameters);
        }
    }

    /**
     * Gets the controller action in raw format.
     *
     * @return string
     */
    protected function getAction()
    {
        $action = $this->route->getAction();

        if (is_string($action) && strpos($action, '::') !== false) {
            return $action;
        }

        return $action;
    }

    /**
     * Gets the controller as a callable.
     *
     * @return callable
     */
    protected function getCallable()
    {
        $action = $this->getAction();

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
     * Generates an array of parameters to send to the controller.
     *
     * @param callable $action
     * @return array
     */
    protected function populateParameters($action)
    {
        $reflection = $this->generateReflection($action);
        $parameters = array();
        $optional = array();

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $class = $param->getClass() ? $param->getClass()->name : null;

            if (!is_null($class)) {
                $parameters[] = $this->getTypedParameter($class, $name);
            }
            else {
                $parameters[] = $this->getCommonParameter($name);
            }

            $optional[] = $param->isOptional();
        }

        $this->trimOptionalParameters($parameters, $optional);

        return $parameters;
    }

    /**
     * Generates a reflection object for the callable action.
     *
     * @param callable $action
     * return \ReflectionMethod|\ReflectionFunction
     */
    protected function generateReflection($action)
    {
        if (is_string($action)) {
            return new \ReflectionMethod($action);
        }

        if (is_array($action)) {
            return new \ReflectionMethod(get_class($action[0]) . '::' . $action[1]);
        }

        if (is_callable($action)) {
            return new \ReflectionFunction($action);
        }
    }

    /**
     * Gets an instance associated with a type-hinted parameter.
     *
     * @return mixed
     */
    protected function getTypedParameter($className, $name)
    {
        $objects = array($this->request, $this->response);

        foreach ($objects as $object) {
            if ($object instanceof $className) {
                return $object;
            }
        }

        $attribute = $this->request->getAttribute($name);

        if (!is_null($attribute) && is_object($attribute)) {
            if ($attribute instanceof $className) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Gets the value of a parameter in the route uri with the specified name.
     *
     * @return string|null
     */
    protected function getCommonParameter($name)
    {
        $param = $this->route->parameter($name);
        if (!is_null($param)) return $param;

        $param = $this->request->get($name);
        if (!is_null($param)) return $param;

        $attribute = $this->request->getAttribute($name);
        return $attribute;
    }

    /**
     * Trims optional parameters off the end of the parameters list if they are null. By doing so, the controller can
     * use its own default values, rather than always receiving null values.
     */
    protected function trimOptionalParameters(array &$parameters, array &$optional)
    {
        $offset = count($parameters) - 1;

        while ($offset >= 0) {
            if (!$optional[$offset]) {
                break;
            }

            if (is_null($parameters[$offset])) {
                unset($parameters[$offset]);
            }

            $offset--;
        }
    }

    /**
     * Creates an instance and returns the callable array to execute it.
     *
     * @param string $name
     * @return callable
     */
    protected function createInstance($name)
    {
        list($className, $method) = explode('::', $name, 2);

        return array(new $className, $method);
    }

}
