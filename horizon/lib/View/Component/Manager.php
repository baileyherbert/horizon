<?php

namespace Horizon\View\Component;

use Exception;
use Horizon\Foundation\Application;
use Horizon\View\Template;
use Horizon\View\ViewException;
use InvalidArgumentException;
use ReflectionClass;
use Twig\Error\RuntimeError;

class Manager
{

    /**
     * @var string[]
     */
    private $registered = array();

    /**
     * @var string[]
     */
    private $contents = array();

    /**
     * Short name of the component currently being compiled.
     *
     * @var string[]
     */
    private $currentlyCompiling = array();

    /**
     * Registers a component template file.
     *
     * @param string $name
     * @param string $path
     */
    public function register($name, $path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException('Cannot register component because no file existed at the given path.');
        }

        $this->registered[$name] = $path;
    }

    /**
     * @param string $componentName
     * @param array $constructorArgs
     * @return string
     *
     * @throws InvalidArgumentException if the component cannot be found.
     * @throws ViewException if the component encounters a render error.
     */
    public function compile($componentName, $constructorArgs = array())
    {
        if (isset($this->currentlyCompiling[$componentName])) {
            throw new ViewException('Component compiler detected an infinite loop.');
        }

        $this->currentlyCompiling[$componentName] = true;

        $viewName = '@component/' . $componentName;
        $absoluteFilePath = $this->getComponentPath($componentName);
        $component = $this->getComponent($absoluteFilePath);

        // Resolve the dependency
        $object = $this->resolve($component['className'], $constructorArgs);

        // Save data
        $this->contents[$viewName] = $component['contents'];

        // Build the context
        $context = Application::kernel()->view()->getContext();
        $context['this'] = $object;

        // Compile
        try {
            $template = new Template($viewName, $context);
            $return = $template->render();

            unset($this->currentlyCompiling[$componentName]);
            return $return;
        }
        catch (\Exception $e) {
            unset($this->currentlyCompiling[$componentName]);

            if ($e instanceof ViewException) throw $e;
            if ($e instanceof RuntimeError) throw $e;

            throw new ViewException(sprintf('%s in component: %s', basename(get_class($e)), $e->getMessage()));
        }
    }

    /**
     * Prepares the component and returns an array consisting of the file 'contents' with the dependency line removed,
     * and 'className' with the extracted dependency class name.
     *
     * @param string $path
     * @return array
     */
    private function getComponent($path)
    {
        $contents = trim(file_get_contents($path));
        $heading = strtok($contents, "\n");

        if (preg_match('/^\s*@using\((\\\'[\w\\\\]+\\\'|"[\w\\\\]+")\)\s*$/', $heading, $matches)) {
            $className = trim($matches[1], '"\'');
            $contents = ltrim(substr($contents, strlen($heading)));

            return array(
                'heading' => $heading,
                'contents' => $contents,
                'className' => $className
            );
        }

        return array(
            'heading' => $heading,
            'contents' => $contents,
            'className' => 'Horizon\View\Component\DynamicComponent'
        );
    }

    /**
     * @param $name
     * @return string
     */
    private function getComponentPath($name)
    {
        // Check for a manually-registered path
        if (isset($this->registered[$name])) {
            return $this->registered[$name];
        }

        // Get all component loaders
        $loaders = app()->all('Horizon\View\ComponentLoader');
        $absolutePath = null;

        foreach ($loaders as $loader) {
            $path = $loader->resolve($name);

            if (!is_null($path)) {
                $absolutePath = $path;
            }
        }

        // If there is no match, throw an exception
        if (is_null($absolutePath)) {
            throw new InvalidArgumentException('No component with the name "' . $name . '" could be found.');
        }

        return $absolutePath;
    }

    /**
     * Returns an instance of the given dependency.
     *
     * @param string $className
     * @param array $args
     * @return object
     * @throws
     */
    private function resolve($className, $args = array())
    {
        if (!is_null($instance = app()->make($className, $args))) {
            return $instance;
        }

        if (class_exists($className)) {
            $reflect = new ReflectionClass($className);
            return $reflect->newInstanceArgs($args);
        }

        // No match, throw an exception
        throw new InvalidArgumentException('Component dependency "' . $className . '" could not be resolved.');
    }

    /**
     * Returns the contents of the given component path (expected format is "@component/name").
     *
     * @param string $name
     * @return string
     * @throws
     */
    public function getFileContents($name)
    {
        if (isset($this->contents[$name])) {
            $contents = $this->contents[$name];
            unset($this->contents[$name]);
            return $contents;
        }

        throw new Exception('Cannot get file contents for "' . $name . '" because they expired.');
    }

}
