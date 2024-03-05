<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Ace\Util\FileOpener;
use Horizon\Console\Command;
use Horizon\Foundation\Application;
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
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the migration.' );
		$this->addOption('open', 'o', InputOption::VALUE_NONE, 'Opens the file with your default PHP editor.');
		$this->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'Creates a migration from a template.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$name = $this->getNormalizedName($in->getArgument('name'));
		$timestamp = time();

		$migrationsDirPath = Application::paths()->migrationsDir();

		$fileName = "{$timestamp}_{$name}.php";
		$filePath = Path::resolve($migrationsDirPath, $fileName);
		$filePathRelative = Application::paths()->getRelative($filePath);

		if (!file_exists($migrationsDirPath)) {
			mkdir($migrationsDirPath, 0755, true);
		}

		if (file_exists($filePath)) {
			throw new RuntimeException('File conflict: ' . $filePath);
		}

		$template = null;
		if ($in->getOption('template')) {
			$template = $in->getOption('template');
		}

		$result = file_put_contents($filePath, $this->getTemplate($template, [
			'timestamp' => $timestamp,
			'description' => 'Describe the migration.'
		]));

		$out->writeln("<fg=green>[✓]</> create $filePathRelative");

		if ($result === false) {
			throw new RuntimeException('Error writing file: ' . $filePath);
		}

		if ($in->getOption('open')) {
			FileOpener::open($filePath);
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
	protected function getTemplate($name = null, $context) {
		$target = 'resources/ace/make/' . (
			$name !== null ? ('migrations/' . $name) : 'migration'
		) . '.twig';

		$path = Path::join(Framework::path($target));

		if (!file_exists($path)) {
			throw new RuntimeException('Unknown template "' . $name . '"');
		}

		$view = new Template($path, $context);
        return $view->render();
	}

}
