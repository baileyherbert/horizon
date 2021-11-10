<?php

namespace Horizon\Ace\Commands\Core;

use Exception;
use Horizon\Console\Command;
use Horizon\Foundation\Application;
use Horizon\View\ComponentLoader;
use Horizon\View\Template;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Error\SyntaxError;

/**
 * This command prepares the application for production.
 *
 * The optimizations it performs are currently as follows:
 *
 * - Caches template files
 */
class BuildCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Builds for production');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$this->clean($out);
		$this->renderViews($out);
	}

	/**
	 * Cleans any files left from the previous build.
	 *
	 * @return void
	 */
	protected function clean(OutputInterface $out) {
		if (file_exists($this->getCachePath())) {
			$this->deleteDir($this->getCachePath());
		}
	}

	/**
	 * Returns an absolute path to the view cache directory.
	 *
	 * @return string
	 */
	protected function getCachePath() {
		return Application::paths()->cache();
	}

	/**
	 * Renders all view templates.
	 *
	 * @return void
	 */
	protected function renderViews(OutputInterface $out) {
		Application::kernel()->view()->boot();
		$loaders = Application::kernel()->view()->getLoaders();
		$numFiles = 0;

		foreach ($loaders as $loader) {
			foreach ($loader->resolveAll() as $fileName) {
				$showName = $this->getRelativeName($fileName);
				$out->writeln("<fg=yellow>[ᐅ]</> compile $showName");
				$numFiles++;

				// Convert component file names to their @component/name equivalents
				// This is essential for component-specific template features to be used in the compilation
				if ($loader instanceof ComponentLoader) {
					$componentName = substr($fileName, strlen($loader->getPath()) + 1);
					$componentName = preg_replace("/(\.blade\.php|\.twig|\.html)$/", "", $componentName);
					$fileName = str_replace('\\', '/', "@component/$componentName");

					// Manually warm the component manager
					Application::kernel()->view()->componentManager()->prepare($componentName);
				}
				else {
					// Calculate the path relative to the loader's directory
					$fileName = substr($fileName, strlen($loader->getPath()) + 1);
					$fileName = str_replace('\\', '/', $fileName);
				}

				try {
					$template = new Template($fileName);
					$template->cache();
				}
				catch (Exception $ex) {
					if ($ex instanceof SyntaxError) {
						$message = lcfirst(rtrim($ex->getMessage(), '.'));
						$line = $ex->getLine();

						$out->writeln("<fg=red>[!]</> syntax error in $showName: $message on line $line");
					}
					else {
						$out->writeln("<fg=red>[!]</> error in $showName: {$ex->__toString()}");
					}

					$this->exit(1);
				}
			}
		}

		$out->writeln('<fg=green>[✓]</> ' . ($numFiles === 0 ? 'nothing to do' : 'cached views'));
	}

	protected function getRelativeName($fileName) {
		$root = Application::root();
		return str_replace('\\', '/', substr($fileName, strlen($root) + 1));
	}

	/**
	 * Deletes the specified directory and all files within it. Careful!
	 *
	 * @param string $dirPath
	 * @return void
	 */
	protected function deleteDir($dirPath) {
		if (!is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath is not a directory");
		}

		if (!is_writable($dirPath)) {
			throw new InvalidArgumentException("$dirPath cannot be deleted due to permissions");
		}

		if (!starts_with($dirPath, Application::root(), true)) {
			throw new InvalidArgumentException("$dirPath is outside of the application");
		}

		$dirPath = rtrim($dirPath, '/\\') . DIRECTORY_SEPARATOR;
		$files = glob($dirPath . '*', GLOB_MARK);

		foreach ($files as $file) {
			if (is_dir($file)) {
				$this->deleteDir($file);
			}
			else if (is_writable($file)) {
				unlink($file);
			}
			else {
				throw new Exception("$file cannot be deleted due to permissions");
			}
		}

		rmdir($dirPath);
	}

}
