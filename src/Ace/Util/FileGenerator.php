<?php

namespace Horizon\Ace\Util;

use Horizon\Foundation\Application;
use Horizon\Foundation\Framework;
use Horizon\Support\Path;
use Horizon\View\Template;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class FileGenerator {

	/**
	 * @var string
	 */
	protected $input;

	/**
	 * Optional namespace to prefix on generated class names.
	 *
	 * @var string|null
	 */
	public $namespace;

	/**
	 * Optional suffix to use for generated class names. If the class name already ends with the suffix, it will not
	 * be appended again.
	 *
	 * @var string|null
	 */
	public $classNameSuffix;

	/**
	 * The base directory to use for generated paths.
	 *
	 * @var string|null
	 */
	public $baseDir;

	/**
	 * The extension to use for the generated file.
	 *
	 * @var string|null
	 */
	public $extension;

	/**
	 * Allows the class resolver to treat colons (`:`) as path separators and namespace prefixes. This is useful for
	 * generating command classes.
	 *
	 * @var bool
	 */
	public $enableColonNamespacing = false;

	/**
	 * Constructs a new `PathBuilder` instance.
	 *
	 * @param string $input
	 */
	public function __construct($input) {
		$this->input = str_replace('\\', '/', trim(trim($input), '/\\'));
	}

	/**
	 * Returns the input as a short class name or path. For example, if the input is "a/b", the result will be "A\B".
	 *
	 * @return string
	 */
	public function getClassInputName() {
		$pattern = $this->enableColonNamespacing ? "/[_.:-]+/" : "/[_.-]+/";
		$segments = preg_split($pattern, $this->input);
		$segments = array_map('ucfirst', $segments);
		$name = implode('', $segments);
		$name = implode('/', array_map('ucfirst', preg_split("/[\/\\\]+/", $name)));

		return $name;
	}

	/**
	 * Returns a fully qualified class name. You can optionally provide a suffix to change the class name.
	 *
	 * @return string
	 */
	public function getClass() {
		$name = $this->getClassInputName();
		$name = $this->getClassPrefix() . $name;

		if ($this->namespace !== null) {
			$name = rtrim($this->namespace, '/\\') . '\\' . $name;
		}

		// The ! character is a secret way to disable suffixes
		if (ends_with($name, '!')) {
			$name = substr($name, 0, -1);
			$suffix = null;
		}

		if (isset($this->classNameSuffix)) {
			$suffix = str_replace('\\', '/', $this->classNameSuffix);

			if (!ends_with($name, $suffix, true)) {
				$name .= $suffix;
			}
		}

		return str_replace('/', '\\', $name);
	}

	/**
	 * Returns a short class name. You can optionally provide a suffix to change the class name. If the class name
	 * already ends with the suffix, it won't be appended.
	 *
	 * @return string
	 */
	public function getClassName() {
		$path = $this->getClass();
		return Path::basename($path);
	}

	/**
	 * Returns a namespace excluding the class name.
	 *
	 * @return string
	 */
	public function getClassNamespace() {
		$path = $this->getClass();
		return Path::dirname($path);
	}

	/**
	 * Returns a class file path.
	 *
	 * @return string
	 */
	public function getClassPath() {
		$name = $this->getClassInputName();
		$name = trim(str_replace('\\', '/', $name), '/');
		$name = $this->getClassPrefix() . $name;

		if (isset($this->classNameSuffix)) {
			$suffix = str_replace('\\', '/', $this->classNameSuffix);

			if (!ends_with($name, $suffix, true)) {
				$name .= $suffix;
			}
		}

		$name .= '.php';

		if (isset($this->baseDir)) {
			$class = Path::join($this->baseDir, $name);
		}

		return $class;
	}

	/**
	 * Returns the absolute path of a class file, resolved within the framework's root directory.
	 *
	 * @return string
	 */
	public function resolveClassPath() {
		return Path::resolve(Application::root(), $this->getClassPath());
	}

	/**
	 * Returns the prefix to use for the class when colon namespacing is enabled.
	 *
	 * @return string
	 */
	protected function getClassPrefix() {
		if ($this->enableColonNamespacing && str_contains($this->input, ':')) {
			$segments = explode(':', $this->input);
			$segments = array_map('ucfirst', $segments);
			$path = Path::dirname(implode('/', $segments));

			$segments = preg_split("/[_.-]+/", $path);
			$segments = array_map('ucfirst', $segments);
			$prefix = implode('', $segments);

			return $prefix . '/';
		}

		return '';
	}

	/**
	 * Asserts that the class name will not conflict with any of the specified symbols.
	 *
	 * @param string[] $conflicts
	 * @return string
	 */
	public function assertClassName(array $conflicts) {
		if (in_array($this->getClassName(), $conflicts)) {
			throw new RuntimeException('Cannot use reserved class name "' . $this->getClassName() . '"');
		}
	}

	/**
	 * Returns the input as a short file name path. For example, if the input is "a/b", the result will be "a/b".
	 * This is just a normalization step, really.
	 *
	 * @param bool $includeExtension
	 * @return string
	 */
	public function getFileInputName($includeExtension = true) {
		$name = trim(trim(str_replace('\\', '/', $this->input)), '/');

		if (isset($this->extension) && $includeExtension) {
			$name .= '.' . trim($this->extension, '.');
		}

		return $name;
	}

	/**
	 * Returns the basename of a file.
	 *
	 * @param bool $includeExtension
	 * @return string
	 */
	public function getFileName($includeExtension = true) {
		$name = $this->getFileInputName($includeExtension);
		return Path::basename($name);
	}

	/**
	 * Returns the full path of a file.
	 *
	 * @param bool $includeExtension
	 * @return string
	 */
	public function getFilePath($includeExtension = true) {
		$name = $this->getFileInputName($includeExtension);

		if (isset($this->baseDir)) {
			$name = Path::join($this->baseDir, $name);
		}

		return $name;
	}

	/**
	 * Returns the absolute path of a file, resolved within the framework's root directory.
	 *
	 * @return string
	 */
	public function resolveFilePath() {
		return Path::resolve(Application::root(), $this->getFilePath());
	}

	/**
	 * Writes the given contents to the file.
	 *
	 * @param string $content
	 * @param OutputInterface $out Optional interface for automatic success logging
	 * @return string
	 */
	public function writeFile($content, OutputInterface $out = null) {
		$this->write($this->resolveFilePath(), $content);

		if (isset($out)) {
			$out->writeln('<fg=green>[✓]</> create ' . Application::paths()->getRelative($this->getFilePath()));
		}
	}

	/**
	 * Writes the given contents to the class file.
	 *
	 * @param string $content
	 * @param OutputInterface $out Optional interface for automatic success logging
	 * @return string
	 */
	public function writeClassFile($content, OutputInterface $out = null) {
		$this->write($this->resolveClassPath(), $content);

		if (isset($out)) {
			$out->writeln('<fg=green>[✓]</> create ' . Application::paths()->getRelative($this->getClassPath()));
		}
	}

	/**
	 * Renders a template inside the `horizon/resources/ace` directory to the file path.
	 *
	 * @param string $templateName
	 * @param array $context
	 * @param OutputInterface $out Optional interface for automatic success logging
	 * @return void
	 */
	public function renderFile($templateName, $context = array(), OutputInterface $out = null) {
		if (!ends_with($templateName, '.twig')) {
			$templateName .= '.twig';
		}

		$path = Path::resolve(Framework::path('resources/ace'), $templateName);
		$view = new Template($path, $context);

		$this->writeFile($view->render(), $out);
	}

	/**
	 * Renders a template inside the `horizon/resources/ace` directory to the class file path.
	 *
	 * @param string $templateName
	 * @param array $context
	 * @param OutputInterface $out Optional interface for automatic success logging
	 * @return void
	 */
	public function renderClassFile($templateName, $context = array(), OutputInterface $out = null) {
		if (!ends_with($templateName, '.twig')) {
			$templateName .= '.twig';
		}

		$context = array_merge([
			'className' => $this->getClassName(),
			'classNamespace' => $this->getClassNamespace(),
			'classPath' => $this->getClassPath(),
			'classNameFull' => $this->getClass()
		], $context);

		$path = Path::resolve(Framework::path('resources/ace'), $templateName);
		$view = new Template($path, $context);

		$this->writeClassFile($view->render(), $out);
	}

	/**
	 * Writes content to a file.
	 *
	 * @param string $path
	 * @param string $content
	 * @return void
	 */
	protected function write($path, $content) {
		$dirName = Path::dirname($path);

		if (!file_exists($dirName)) {
			if (!mkdir($dirName, 0755, true)) {
				throw new RuntimeException("Failed to create directory: $dirName");
			}
		}

		if (file_exists($path)) {
			throw new RuntimeException("Existing file conflict: $path");
		}

		if (!is_writable($dirName)) {
			throw new RuntimeException("Directory is not writable: $dirName");
		}

		if (file_put_contents($path, $content) === false) {
			throw new RuntimeException("Failed to write file: $path");
		}
	}

}
