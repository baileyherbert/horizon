<?php

namespace Horizon\View;

use Horizon\Support\Path;

class ViewLoader {

	/**
	 * The root path for the file loader.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Constructs a new ViewLoader in the specified directory path and under the specified extension.
	 *
	 * @param string $path
	 */
	public function __construct($path) {
		$this->path = $path;
	}

	/**
	 * Resolves a view file given its relative name and returns the absolute path if it exists. Otherwise, returns null.
	 *
	 * @param string $viewFileName
	 * @return string|null
	 */
	public function resolve($viewFileName) {
		// Resolve the exact file name
		if (file_exists($path = Path::resolve($this->path, $viewFileName)) && !is_dir($path)) {
			return $path;
		}

		// Resolve the file name with ".twig" suffix
		if (file_exists($path = Path::resolve($this->path, $viewFileName . '.twig')) && !is_dir($path)) {
			return $path;
		}

		// Resolve the file name with ".blade.php" suffix
		if (file_exists($path = Path::resolve($this->path, $viewFileName . '.blade.php')) && !is_dir($path)) {
			return $path;
		}

		// No match
		return null;
	}

	/**
	 * Returns the path to the directory where this loader's files are stored.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Resolves all view templates in the loader and returns them as absolute paths.
	 *
	 * @return string[]
	 */
	public function resolveAll() {
		return $this->getFiles($this->path);
	}

	/**
	 * Recursively fetches templates from a directory.
	 *
	 * @param string $dir
	 * @param string[] $results
	 * @return string[]
	 */
	private function getFiles($dir, &$results = array()) {
		$files = scandir($dir);

		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);

			if (!is_dir($path)) {
				if (ends_with($path, ['.blade.php', '.twig', '.html'])) {
					$results[] = $path;
				}
			}
			else if ($value != "." && $value != "..") {
				$this->getFiles($path, $results);
			}
		}

		return $results;
	}

}
