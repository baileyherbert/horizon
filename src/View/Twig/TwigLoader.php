<?php

namespace Horizon\View\Twig;

use Exception;
use Horizon\Foundation\Application;
use Twig_Extension;
use Twig_Environment;
use Horizon\Foundation\Framework;
use Horizon\Support\Path;
use Horizon\View\Template;

class TwigLoader {

	/**
	 * @var Template
	 */
	protected $template;

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
	public function __construct(Template $template) {
		$this->template = $template;
		$this->loader = $this->createTwigLoader();
		$this->environment = $this->createTwigEnvironment();
	}

	/**
	 * Compiles and renders the template. Returns the resulting content as a string.
	 *
	 * @return string
	 */
	public function render() {
		$output = $this->environment->render(
			$this->template->getPath(),
			$this->template->getContext()
		);

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
	public function cache() {
		$this->environment->loadTemplate($this->template->getPath());
	}

	/**
	 * Creates the twig loader instance.
	 *
	 * @return TwigFileLoader
	 */
	protected function createTwigLoader() {
		return new TwigFileLoader($this);
	}

	/**
	 * Creates the twig environment instance.
	 */
	protected function createTwigEnvironment() {
		// An array to store environment options
		$options = array(
			'cache' => $this->getCacheDirectory(),
			'auto_reload' => true
		);

		// Create the environment instance with the options
		$environment = new Twig_Environment($this->loader, $options);

		// Add extensions
		$this->addExtensions($environment, (new TwigExtensionLoader($this->loader))->getExtensions());

		return $environment;
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
		return config('app.view_cache', false);
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
