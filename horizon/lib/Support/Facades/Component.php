<?php

namespace Horizon\Support\Facades;

use Horizon\View\ViewException;
use InvalidArgumentException;

/**
 * Facade for interacting with components.
 */
class Component
{

	/**
	 * Registers the given component name to a template at the given absolute path.
	 *
	 * @param string $componentName
	 * @param string $absolutePath
	 */
	public static function register($componentName, $absolutePath)
	{
		\Horizon\Foundation\Application::kernel()->view()->componentManager()->register($componentName, $absolutePath);
	}

	/**
	 * Compiles a component with the given name and returns the output.
	 *
	 * @param string $componentName
	 * @param mixed ...$args
	 * @return string
	 *
	 * @throws InvalidArgumentException if the component cannot be found.
	 * @throws ViewException if the component encounters a render error.
	 */
	public static function compile($componentName)
	{
		$args = func_get_args();
		$componentName = array_shift($args);

		return \Horizon\Foundation\Application::kernel()->view()->componentManager()->compile($componentName, $args);
	}

}
