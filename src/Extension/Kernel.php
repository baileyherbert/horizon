<?php

namespace Horizon\Extension;

use Horizon\Foundation\Application;
use Horizon\Foundation\Services\Autoloader;
use Horizon\Support\Profiler;
use Horizon\Support\Services\ServiceProvider;

/**
 * Kernel for extensions.
 */
class Kernel {

	/**
	 * @var Extension[]
	 */
	private $extensions = array();

	/**
	 * @var Exception[]
	 */
	private $exceptions = array();

	/**
	 * Boots the extension kernel.
	 */
	public function boot() {
		Profiler::record('Boot extension kernel');
		$this->resolve();
	}

	/**
	 * Autoloads extensions through their namespace mapping and vendor directories.
	 */
	public function autoload() {
		foreach ($this->extensions as $extension) {
			// Autoload namespaces
			foreach ($extension->getNamespaces() as $namespace => $absolutePath) {
				Autoloader::mount($namespace, $absolutePath);
			}

			// Autoload files
			foreach ($extension->getFiles() as $absolutePath) {
				Autoloader::vendor($absolutePath);
			}
		}
	}

	/**
	 * Loads providers from extensions and registers them in the application.
	 */
	public function provide() {
		foreach ($this->extensions as $extension) {
			$providers = $extension->getProviders();

			foreach ($providers as $className) {
				if (class_exists($className)) {
					$provider = new $className;

					if ($provider instanceof ServiceProvider) {
						Application::register($provider);
					}
				}
			}
		}
	}

	/**
	 * Returns all loaded extensions.
	 *
	 * @return Extension[]
	 */
	public function get() {
		return $this->extensions;
	}

	/**
	 * Returns an array of all exceptions that occurred during extension initialization. This can be useful to find
	 * broken extensions.
	 *
	 * @return Exception[]
	 */
	public function getExceptions() {
		return $this->exceptions;
	}

	/**
	 * Resolves extensions from service providers and stores them internally.
	 */
	private function resolve() {
		foreach (Application::collect('Horizon\Extension\Extension') as $extension) {
			$this->extensions[] = $extension;
		}

		foreach (Application::collect('Horizon\Extension\Exception') as $exception) {
			$this->exceptions[] = $exception;
		}
	}

}
