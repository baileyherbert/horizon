<?php

namespace Horizon\Foundation;

use Exception;
use Horizon\Support\Path;

/**
 * This class helps retrieve paths to the various core components of the application and framework. You can retrieve an
 * instance of it using `Horizon\Foundation\Application::paths()`.
 */
class ApplicationPathProvider {

	/**
	 * @var string
	 */
	private $root;

	/**
	 * @var string[]
	 */
	private $cache = array();

	public static $count = 0;

	public function __construct() {
		$this->root = env('ROOT');
	}

	/**
	 * Returns an absolute path to the `config` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function config($path = '') {
		return $this->make(env('CONFIG', 'app/config'), $path);
	}

	/**
	 * Returns an absolute path to the `errors` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function errorLog() {
		return $this->make('app/error_log', null, 'error_log');
	}

	/**
	 * Returns an absolute path to the `routes` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function routes($path = '') {
		return $this->make('app/routes', $path, 'routes');
	}

	/**
	 * Returns an absolute path to the `public` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function public($path = '') {
		return $this->make('app/public', $path, 'public');
	}

	/**
	 * Returns an absolute path to the `errors` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function errors($path = '') {
		return $this->make('app/errors', $path, 'errors');
	}

	/**
	 * Returns an absolute path to the `vendor` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function vendor($path = '') {
		return $this->make('vendor', $path, 'vendor');
	}

	/**
	 * Returns an absolute path to the `views` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function views($path = '') {
		return $this->make('app/views', $path, 'views');
	}

	/**
	 * Returns an absolute path to the `embeds` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function embeds($path = '') {
		return $this->make('app/embeds', $path, 'embeds');
	}

	/**
	 * Returns an absolute path to the `components` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function components($path = '') {
		return $this->make('app/components', $path, 'components');
	}

	/**
	 * Returns an absolute path to the `cache` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function cache($path = '') {
		return $this->make('app/cache', $path, 'cache');
	}

	/**
	 * Returns an absolute path to the `translations` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function translations($path = '') {
		return $this->make('app/translations', $path, 'translations');
	}

	/**
	 * Returns an absolute path to the `src` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function src($path = '') {
		return $this->make('app/src', $path, 'src');
	}

	/**
	 * Returns an absolute path to the `extensions` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function extensions($path = '') {
		return $this->make('extensions', $path, 'extensions');
	}

	/**
	 * Returns an absolute path to the `migrations` directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function migrations($path = '') {
		return $this->make('app/database/migrations', $path, 'migrations');
	}

	/**
	 * Returns an absolute path to the root directory.
	 *
	 * @param string $path
	 * @return string
	 */
	public function root($path = '') {
		$key = 'root:' . $path;

		if (!isset($this->cache[$key])) {
			$path = ltrim($path, '\\/');
			$this->cache[$key] = Path::join($this->root, $path);
		}

		return $this->cache[$key];
	}

	/**
	 * Makes a path.
	 *
	 * @param string $defaultPath
	 * @param string|null $relativePath
	 * @param string $customKey
	 * @return string
	 */
	private function make($defaultPath, $relativePath, $customKey = null) {
		$key = "$defaultPath:$relativePath";

		if (is_null($relativePath)) {
			$relativePath = '';
		}

		if (!isset($this->cache[$key])) {
			$relativePath = ltrim($relativePath, '\\/');
			$path = ltrim($customKey ? config('app.paths.' . $customKey, $defaultPath) : $defaultPath, '\\/');

			$this->cache[$key] = Path::join($this->root, $path, $relativePath);
		}

		return $this->cache[$key];
	}

	/**
	 * Accepts an absolute path to a file within the application, and returns a path which is relative to the
	 * application's root directory. Returns `null` if the path is not inside the application.
	 *
	 * @param string $path
	 * @return string|null
	 */
	public function getRelative($path) {
		$path = Path::resolve($path);

		if (starts_with($path, $this->root, true)) {
			return str_replace('\\', '/', substr($path, strlen($this->root) + 1));
		}

		return null;
	}

}
