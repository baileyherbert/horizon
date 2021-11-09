<?php

namespace Horizon\Foundation;

use Exception;
use Horizon\Foundation\Services\Environment;
use Horizon\Support\Path;

/**
 * This class helps bootstrap the framework in various modes.
 */
class Bootstrapper {

	/**
	 * Bootstraps as a web application. Pass `router` or `legacy` as the first parameter for the router type. Pass an
	 * absolute path to the application's root directory where the composer.json file can be found. If a path is not
	 * supplied, it will be detected automatically.
	 *
	 * @param string $mode 'router' or 'legacy'
	 * @param string $baseDir
	 * @return void
	 */
	public static function startWebApplication($mode, $baseDir = null) {
		Environment::set('ROOT', static::getRootPath($baseDir));
		Environment::set('HORIZON_MODE', 'web');
		Environment::set('ROUTING_MODE', $mode);

		Application::kernel()->boot();
	}

	/**
	 * Bootstraps as a console application. Pass an absolute path to the application's root directory where the
	 * composer.json file can be found. If a path is not supplied, it will be detected automatically.
	 *
	 * @param string $baseDir
	 * @return void
	 */
	public static function startConsoleApplication($baseDir = null) {
		if (isset($_SERVER['REMOTE_ADDR'])) {
			echo "Error: Unsupported environment";
			exit(1);
		}

		Environment::set('ROOT', static::getRootPath($baseDir));
		Environment::set('HORIZON_MODE', 'console');

		Application::kernel()->boot();
	}

	/**
	 * Bootstraps the application for testing. Pass an absolute path to the application's root directory where the
	 * composer.json file can be found. If a path is not supplied, it will be detected automatically.
	 *
	 * @return void
	 */
	public static function startTest($baseDir = null) {
		Environment::set('ROOT', static::getRootPath($baseDir));
		Environment::set('APP_MODE', 'development');
	}

	/**
	 * Returns an absolute path to the application's root directory.
	 *
	 * @param string|null $baseDir
	 * @return string
	 */
	private static function getRootPath($baseDir = null) {
		if (isset($baseDir)) {
			return $baseDir;
		}

		$reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
		$path = dirname(dirname(dirname($reflection->getFileName())));

		while (true) {
			$composerPath = Path::join($path, 'composer.json');

			if (file_exists($composerPath)) {
				return $path;
			}

			if (($nextPath = dirname($path)) === $path) {
				throw new Exception('Failed to detect the root application directory!');
			}

			$path = $nextPath;
		}
	}

}
