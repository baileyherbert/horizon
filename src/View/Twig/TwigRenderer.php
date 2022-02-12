<?php

namespace Horizon\View\Twig;

use Exception;
use Horizon\Foundation\Application;
use Twig_Extension;
use Twig_Environment;
use Horizon\Support\Profiler;
use Horizon\View\Template;

class TwigRenderer {

	/**
	 * @var TwigFileLoader
	 */
	protected $loader;

	/**
	 * @var Twig_Environment
	 */
	protected $environment;

	/**
	 * Constructs a new TwigLoader instance.
	 *
	 * @param Template $template
	 */
	public function __construct() {
		$this->loader = $this->createTwigLoader();
		$this->environment = $this->createTwigEnvironment();
		$this->initGlobals();
	}

	/**
	 * Compiles and renders the template. Returns the resulting content as a string.
	 *
	 * @return string
	 */
	public function render(Template $template) {
		Profiler::record('Render template: ' . $template->getPath());

		$startTime = microtime(true);
		$output = $this->environment->render(
			$template->getPath(),
			$template->getContext()
		);

		$took = microtime(true) - $startTime - $this->loader->transpileTime;
		Profiler::recordAsset('View render', $template->getPath(), $took);

		if ($this->loader->isDebuggingEnabled()) {
			$output = str_replace('&#!123;', '{', $output);
			$output = str_replace('&#!125;', '}', $output);
		}


		return $output;
	}

	/**
	 * Compiles and caches the template (if caching is enabled).
	 *
	 * @return string
	 */
	public function cache(Template $template) {
		Profiler::record('Render template (cache): ' . $template->getPath());
		$this->environment->loadTemplate($template->getPath());
	}

	/**
	 * Creates the twig loader instance.
	 *
	 * @return TwigFileLoader
	 */
	protected function createTwigLoader() {
		Profiler::record('Initialize twig file loader');
		return new TwigFileLoader();
	}

	/**
	 * Creates the twig environment instance.
	 */
	protected function createTwigEnvironment() {
		Profiler::record('Initialize twig environment');

		// An array to store environment options
		$options = array(
			'cache' => $this->getCacheDirectory(),
			'auto_reload' => config('app.view_cache_reload', true)
		);

		// Create the environment instance with the options
		$environment = new Twig_Environment($this->loader, $options);

		// Add extensions
		$this->addExtensions($environment, (new TwigExtensionLoader($this->loader))->getExtensions());

		return $environment;
	}

	/**
	 * Sets global variables on the environment.
	 *
	 * @return void
	 */
	protected function initGlobals() {
		// Add the request instance
		$this->environment->addGlobal('request', request());

		if (request()) {
			// Add inputs
			$this->environment->addGlobal('get', request()->query->all());
			$this->environment->addGlobal('post', request()->request->all());
			$this->environment->addGlobal('input', request()->query->all() + request()->request->all());

			// Add sessions
			$session = [];
			$flash = [];

			if (request()->hasSession()) {
				$session = session()->all();
				$flash = session()->temp();
			}

			$this->environment->addGlobal('session', $session);
			$this->environment->addGlobal('flash', $flash);
		}
	}

	/**
	 * Adds the provided extensions to the Twig environment instance.
	 *
	 * @param Twig_Environment $environment
	 * @param Twig_Extension[] $extensions
	 */
	protected function addExtensions(Twig_Environment $environment, array $extensions) {
		foreach ($extensions as $extension) {
			$environment->addExtension($extension);
		}
	}

	/**
	 * Checks if caching is enabled in the configuration.
	 *
	 * @return bool
	 */
	protected function isCacheEnabled() {
		return Application::kernel()->view()->getCacheEnabled();
	}

	/**
	 * Gets an absolute path to the cache directory, or false if it doesn't exist and failed to be created.
	 *
	 * @return string|false
	 */
	protected function getCacheDirectory() {
		$path = Application::paths()->cache();

		if (!$this->isCacheEnabled()) {
			return false;
		}

		if (!file_exists($path)) {
			if (!mkdir($path, 0755, true)) {
				throw new Exception('Failed to create cache directory: ' . $path);
			}
		}

		return $path;
	}

}
