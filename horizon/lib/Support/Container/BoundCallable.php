<?php

namespace Horizon\Support\Container;

use Exception;
use Horizon\Support\Str;
use ReflectionException;
use ReflectionParameter;

/**
 * Provides contextual parameter binding to a callable instance.
 */
class BoundCallable
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var object[]
     */
    private $objects = array();

    /**
     * @var mixed[]
     */
    private $variables = array();

    /**
     * Constructs a new bound callable with the given container.
     *
     * @param callable $callable
     * @param Container|null $container
     * @throws Exception
     */
    public function __construct($callable, Container $container = null)
    {
        $this->callable = $this->getProperCallable($callable);
        $this->container = $container;
    }

    /**
     * Executes the callable after automatically resolving any parameters.
     *
     * @return mixed
     * @throws
     */
    public function execute()
    {
        $reflection = $this->getReflection();
        $parameters = $this->resolve($reflection->getParameters());

        return call_user_func_array($this->callable, $parameters);
    }

    /**
     * Adds an object which will be used as a dependency for parameters requesting an object of the same class.
     *
     * @param object $object
     */
    public function with($object)
    {
        if (is_object($object)) {
            $this->objects[get_class($object)] = $object;
        }
    }

    /**
     * Adds a variable which will be used to resolve dependencies for parameters with a matching name and no type
     * specification. Note that the name is case-sensitive.
     *
     * @param string $name
     * @param mixed $value
     */
    public function where($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Checks if the callable has a variable with the specified name.
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->variables[$name]);
    }

    /**
     * Checks if the callable has the specified class.
     *
     * @param string $className
     * @return bool
     */
    public function contains($className)
    {
        return isset($this->objects[$className]);
    }

    /**
     * Returns the resolved dependencies for the given parameters in their exact order. Parameters which could not be
     * resolved will be provided as their default values or null.
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws ReflectionException
     */
    private function resolve($parameters)
    {
        $resolved = array();

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $class = $parameter->getClass() ? $parameter->getClass()->name : null;

            if (!is_null($class)) {
                // Resolve parameter as an added class dependency
                if (isset($this->objects[$class])) {
                    $resolved[] = $this->objects[$class];
                    continue;
                }

                // Resolve parameter as a provided class dependency
                $collection = $this->container->resolve($class);
                $provided = $collection->first();

                if (!is_null($provided)) {
                    $resolved[] = $provided;
                    continue;
                }
            }
            else {
                // Resolve parameter as a variable by name
                if (isset($this->variables[$name])) {
                    $resolved[] = $this->variables[$name];
                    continue;
                }
            }

            // Resolve with default value or null
            $resolved[] = ($parameter->isOptional()) ? $parameter->getDefaultValue() : null;
        }

        return $resolved;
    }

    /**
     * Generates a reflection object for the callable..
     *
     * @return \ReflectionMethod|\ReflectionFunction
     */
    private function getReflection()
    {
        try {
            if (is_string($this->callable)) {
                return new \ReflectionMethod($this->callable);
            }

            if (is_array($this->callable)) {
                return new \ReflectionMethod(get_class($this->callable[0]) . '::' . $this->callable[1]);
            }

            if (is_callable($this->callable)) {
                return new \ReflectionFunction($this->callable);
            }
        }
        catch (ReflectionException $e) {}

        return null;
    }

    /**
     * Makes the callable proper.
     *
     * @param callable $callable
     * @return callable
     * @throws Exception
     */
    private function getProperCallable($callable)
    {
        if (is_array($callable)) {
            if (!count($callable)) {
                throw new Exception('Not a callable.');
            }

            if (count($callable) == 1) {
                $callable[1] = '__invoke';
            }

            if (is_string($callable[0])) {
                $className = $callable[0];
                $callable[0] = new $className;
            }
        }

        else if (is_string($callable)) {
            list($className, $methodName) = Str::parseCallback($callable);
            $callable = array(new $className, $methodName);
        }

        else {
            throw new Exception('Not a callable.');
        }

        return $callable;
    }

}
