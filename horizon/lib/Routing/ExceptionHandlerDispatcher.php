<?php

namespace Horizon\Routing;

use Exception;
use Horizon\Http\Request;
use Horizon\Http\Response;
use Horizon\Http\Exception\HttpResponseException;
use Horizon\Support\Container\BoundCallable;

class ExceptionHandlerDispatcher extends ControllerDispatcher
{

	/**
	 * @var Exception
	 */
    protected $exception;

	/**
	 * @var RouteGroup
	 */
	protected $group;

    /**
     * Constructs a new ExceptionHandlerDispatcher instance.
     *
     * @param Request $request
     * @param Response $response
     * @param RouteGroup $group
     * @param Exception $ex
     */
    public function __construct(Request $request, Response $response, RouteGroup $group, Exception $ex)
    {
        $this->request = $request;
		$this->response = $response;
        $this->exception = $ex;
        $this->group = $group;
    }

    /**
     * Creates a service-bound callable for the given action.
     *
     * @param string|callable $action
     * @return BoundCallable
     * @throws Exception
     */
    protected function createBoundCallable($action)
    {
		$callable = parent::createBoundCallable($action);
		$callable->with($this->exception, true);

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
		$handler = $this->group->getExceptionHandler();
		$action = RouteAction::parse($handler);

        if (is_string($action) && strpos($action, '::') !== false) {
            list($className, $methodName) = explode('::', $action, 2);

            if (!class_exists($className)) {
                throw new Exception(strpos('Cannot find exception handler %s', $action));
            }

            $callable = new $className();

            if ($methodName == '%') {
                $methodName = strtolower($this->request->getMethod());
            }

            if (!method_exists($callable, $methodName)) {
                throw new Exception(strpos('Cannot find exception handler %s', $action));
            }

            return array($callable, $methodName);
        }

        return $action;
    }

}
