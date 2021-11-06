<?php

namespace Horizon\View;

use Horizon\Foundation\Application;
use Horizon\View\Component\Manager;

/**
 * Kernel for views.
 */
class Kernel {

	/**
	 * @var ViewLoader[]
	 */
	private $viewLoaders = array();

	/**
	 * The latest context used to render a template.
	 *
	 * @var array
	 */
	private $context = array();

	/**
	 * @var Manager
	 */
	private $componentManager;

	/**
	 * Boots the kernel.
	 */
	public function boot() {
		$this->viewLoaders = Application::collect('Horizon\View\ViewLoader');
	}

	/**
	 * Resolves a template name to an absolute path, or returns null if it wasn't found.
	 *
	 * @param string $templateName
	 * @return string|null
	 */
	public function resolveView($templateName) {
		$path = null;

		foreach ($this->viewLoaders as $loader) {
			$absolute = $loader->resolve($templateName);

			if (!is_null($absolute)) {
				$path = $absolute;
			}
		}

		return $path;
	}

	/**
	 * Sets the internal context to use for rendering components.
	 *
	 * @param array $context
	 */
	public function setContext($context = array()) {
		$this->context = $context;
	}

	/**
	 * Returns the internal context.
	 *
	 * @return array
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Returns the internal component manager.
	 *
	 * @return Manager
	 */
	public function componentManager() {
		if (is_null($this->componentManager)) {
			$this->componentManager = new Manager();
		}

		return $this->componentManager;
	}

}
