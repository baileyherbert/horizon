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

class MakeMigrationCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Makes a new migration file');

		$this->addArgument(
			'name',
			InputArgument::REQUIRED,
			'The name of the migration.'
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
		$timestamp = time();

		$migrationPath = config('app.paths.migrations', ['app/database/migrations'])[0];

		$fileName = "{$timestamp}_{$name}.php";
		$dirPath = Framework::path($migrationPath);
		$filePath = Path::resolve($dirPath, $fileName);
		$filePathRelative = str_replace('\\', '/', substr($filePath, strlen(Framework::path()) + 1));

		if (!file_exists($dirPath)) {
			mkdir($dirPath, 0755, true);
		}

		if (file_exists($filePath)) {
			throw new RuntimeException('File conflict: ' . $filePath);
		}

		$result = file_put_contents($filePath, $this->getTemplate([
			'timestamp' => $timestamp,
			'description' => 'Describe the migration.'
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
		$name = trim(str_lower($name));
		$name = str_replace("'", '', $name);
		$name = preg_replace("/[^a-z0-9_]+/", "_", $name);
		$name = preg_replace("/_+/", '_', $name);
		$name = trim($name, '_-');

		return $name;
	}

	/**
	 * Renders the migration class template.
	 *
	 * @param array $context
	 * @return string
	 */
	protected function getTemplate($context) {
		$path = Path::join(Framework::path('horizon'), 'resources/ace/make/migration.twig');
		$view = new Template($path, $context);
        return $view->render();
	}

}
