<?php

namespace Horizon\View;

use Horizon\Foundation\Application;
use Horizon\Foundation\Framework;
use Horizon\Support\Path;
use Horizon\Support\Services\ServiceProvider;
use Horizon\View\Twig\TwigFileLoader;

/**
 * Provides view mounts that can be used to load view files.
 */
class ViewServiceProvider extends ServiceProvider {

	public function register() {
		$this->bind('Horizon\View\ViewLoader', function() {
			return new ViewLoader(Application::paths()->viewsDir());
		});

		$this->bind('Horizon\View\ViewExtension', function(TwigFileLoader $loader) {
			$extensions = array();

			foreach ($this->getExtensionDirectories() as $rootNamespace => $dir) {
				$extensions = array_merge($extensions, $this->fetchExtensionDirectory($rootNamespace, $dir, $loader));
			}

			return $extensions;
		});

		$this->bind('Horizon\View\ComponentLoader', function() {
			return new ComponentLoader(Application::paths()->componentsDir());
		});
	}

	public function provides() {
		return array(
			'Horizon\View\ViewLoader',
			'Horizon\View\ViewExtension',
			'Horizon\View\ComponentLoader'
		);
	}

	/**
	 * Loads applicable extensions from the specified namespace and directory, returning an array of extension
	 * instances.
	 *
	 * @return \Twig_Extension[]
	 */
	protected function fetchExtensionDirectory($namespace, $dir, $loader) {
		if (!file_exists($dir) || !is_dir($dir)) {
			return array();
		}

		$files = scandir($dir);
		$extensions = array();

		foreach ($files as $file) {
			$path = Path::join($dir, $file);
			$className = $namespace . '\\' . Path::basename($file, '.php');

			if ($file == '.' || $file == '..') {
				continue;
			}

			if (is_dir($file)) {
				$recursive = $this->fetchExtensionDirectory(($namespace . '\\' . $file), $path, $loader);

				foreach ($recursive as $extension) {
					$extensions[] = $extension;
				}
			}
			else {
				if (class_exists($className)) {
					$extension = new $className($loader);

					if ($extension instanceof \Twig_Extension) {
						$extensions[] = $extension;
					}
				}
			}
		}

		return $extensions;
	}

	/**
	 * Gets an array of directories and namespaces which should contain extensions. This does not check existence,
	 * and may return paths that are missing or point to files.
	 *
	 * @return array
	 */
	protected function getExtensionDirectories() {
		return array(
			'App\View\Extensions' => Application::paths()->srcDir('View/Extensions'),
			'Horizon\View\Extensions' => Framework::path('src/View/Extensions')
		);
	}

}
