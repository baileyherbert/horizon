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

class MakeControllerCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Makes a new controller file');

		$this->addArgument(
			'name',
			InputArgument::REQUIRED,
			'The name of the controller.'
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
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$name = $this->getNormalizedName($in->getArgument('name'));
		$className = Path::basename($name);

		if (in_array($className, ['Controller', 'Request', 'Response'])) {
			throw new RuntimeException('Cannot use reserved class name "' . $className . '"');
		}

		$filePath = Path::resolve(
			Framework::path($in->getOption('root') ? 'app/src' : 'app/src/Http/Controllers'),
			"$name.php"
		);

		$dirPath = Path::dirname($filePath);
		$filePathRelative = str_replace('\\', '/', substr($filePath, strlen(Framework::path()) + 1));

		$namespace = ($in->getOption('root') ? 'App/' : 'App/Http/Controllers/') . $name;
		$namespace = str_replace('/', '\\', $namespace);

		if (!file_exists($dirPath)) {
			mkdir($dirPath, 0755, true);
		}

		if (file_exists($filePath)) {
			throw new RuntimeException('File conflict: ' . $filePath);
		}

		$result = file_put_contents($filePath, $this->getTemplate([
			'name' => $className,
			'namespace' => $namespace
		]));

		$out->writeln("<fg=green>[✓]</> create $filePathRelative");

		if ($result === false) {
			throw new RuntimeException('Error writing file: ' . $filePath);
		}

		if ($in->getOption('open')) {
			exec('start ' . $filePath);
		}
	}

	/**
	 * Returns the migration name as a normalized string for use in the file name.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getNormalizedName($name) {
		$name = str_replace('\\', '/', $name);
		$name = ucfirst(trim(trim($name), '/'));

		if (!ends_with($name, 'controller', true)) {
			$name .= 'Controller';
		}

		return $name;
	}

	/**
	 * Renders the migration class template.
	 *
	 * @param array $context
	 * @return string
	 */
	protected function getTemplate($context) {
		$path = Path::join(Framework::path('horizon'), 'resources/ace/make/controller.twig');
		$view = new Template($path, $context);
        return $view->render();
	}

}
