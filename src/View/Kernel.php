<?php

namespace Horizon\View;

use Horizon\Foundation\Application;
use Horizon\Support\Profiler;
use Horizon\View\Component\Manager;
use Horizon\View\Twig\TwigRenderer;

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
	 * The instance to use for rendering or `null` if not yet instantiated.
	 *
	 * @var TwigRenderer|null
	 */
	private $renderer;

	/**
	 * Whether or not caching is currently forced.
	 *
	 * @var false
	 */
	private $cacheForced = false;

	/**
	 * Boots the kernel.
	 */
	public function boot() {
		Profiler::record('Boot view kernel');

		$this->viewLoaders = array_merge(
			Application::collect('Horizon\View\ViewLoader')->all(),
			Application::collect('Horizon\View\ComponentLoader')->all()
		);
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

	/**
	 * Returns an array of view loaders.
	 *
	 * @return ViewLoader[]
	 */
	public function getLoaders() {
		return array_values($this->viewLoaders);
	}

	/**
	 * Returns the instance to use for rendering.
	 *
	 * @return TwigRenderer
	 */
	public function getRenderer() {
		if (is_null($this->renderer)) {
			$this->renderer = new TwigRenderer();
		}

		return $this->renderer;
	}

	/**
	 * Returns true if caching is currently enabled.
	 *
	 * @return bool
	 */
	public function getCacheEnabled() {
		return $this->cacheForced || config('app.view_cache', false);
	}

	/**
	 * Returns true if caching is currently forced.
	 *
	 * @return bool
	 */
	public function getCacheForced() {
		return $this->cacheForced;
	}

	/**
	 * Sets the state value of cache enforcement. This will degrade performance and should generally be used within
	 * build processes only.
	 *
	 * @param bool $state
	 * @return void
	 */
	public function setCacheForced($state) {
		$this->cacheForced = $state;
	}

}
