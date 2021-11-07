<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Console\Command;
use Horizon\Foundation\Framework;
use Horizon\Support\Path;
use Horizon\View\Template;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Makes a new model file');

		$this->addArgument(
			'name',
			InputArgument::REQUIRED,
			'The name of the view file.'
		);

		$this->addOption(
			'root',
			'r',
			InputOption::VALUE_NONE,
			'Uses the root source directory.'
		);

		$this->addOption(
			'open',
			'o',
			InputOption::VALUE_NONE,
			'Opens the file with your default PHP editor.'
		);

		$this->addOption(
			'timestamps',
			't',
			InputOption::VALUE_NONE,
			'Adds timestamps to the model.'
		);
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$className = $this->getClassName($in);
		$classNameShort = Path::basename($className);
		$classNamespace = Path::dirname($className);

		$targetFilePath = $this->getClassPath($in);
		$targetDirPath = Path::dirname($targetFilePath);

		$relativeFilePath = str_replace('\\', '/', substr($targetFilePath, strlen(Framework::path()) + 1));

		if (in_array($classNameShort, ['Model'])) {
			throw new RuntimeException('Cannot use reserved class name "' . $className . '"');
		}

		// Ensure the target directory exists
		if (!file_exists($targetDirPath)) {
			if (!mkdir($targetDirPath, 0755, true)) {
				throw new RuntimeException("Failed to create directory: $targetDirPath");
			}
		}

		// Make sure the file doesn't exist
		if (file_exists($targetFilePath)) {
			throw new RuntimeException("Existing file conflict: $targetFilePath");
		}

		// Build the file contents
		$content = $this->getTemplate([
			'namespace' => $classNamespace,
			'table' => $this->getTableName($classNameShort),
			'name' => $classNameShort,
			'timestamps' => !!$in->getOption('timestamps')
		]);

		// Create the file
		if (false === file_put_contents($targetFilePath, $content)) {
			throw new RuntimeException("Failed to create file: $targetFilePath");
		}

		$out->writeln("<fg=green>[✓]</> create $relativeFilePath");

		// Open in editor
		if ($in->getOption('open')) {
			exec('start ' . $targetFilePath);
		}
	}

	/**
	 * Returns the migration name as a normalized string for use in the file name.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getNormalizedName($name) {
		return trim(trim(str_replace('\\', '/', $name)), '/');
	}

	/**
	 * Returns the full class name to use.
	 *
	 * @param InputInterface $in
	 * @return string
	 */
	protected function getClassName(InputInterface $in) {
		$name = ucfirst(trim(trim($in->getArgument('name')), '/'));
		$namespace = ($in->getOption('root') ? 'App/' : 'App/Database/Models/') . $name;
		$namespace = str_replace('/', '\\', $namespace);

		return $namespace;
	}

	/**
	 * Returns the absolute path to use.
	 *
	 * @param InputInterface $in
	 * @return string
	 */
	protected function getClassPath(InputInterface $in) {
		$name = ucfirst(trim(trim($in->getArgument('name')), '/'));
		$namespace = ($in->getOption('root') ? 'app/src' : 'app/src/Database/Models/');

		return Path::resolve(Framework::path($namespace), $name . '.php');
	}
	/**
	 * Renders the migration class template.
	 *
	 * @param array $context
	 * @return string
	 */
	protected function getTemplate($context) {
		$path = Path::join(Framework::path('horizon'), 'resources/ace/make/model.twig');
		$view = new Template($path, $context);
        return $view->render();
	}

	/**
	 * Returns a best guess for the name of the table based on the class name.
	 *
	 * @param string $className
	 * @return string
	 */
	protected function getTableName($className) {
		$tableName = trim(strtolower(preg_replace("/([A-Z])/", '_$1', $className)), '_');

		if (substr($tableName, 0, -1) != 's') {
			$tableName .= 's';
		}

		return $tableName;
	}

}
