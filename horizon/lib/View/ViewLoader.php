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

}
