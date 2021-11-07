<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Console\Command;
use Horizon\Foundation\Framework;
use Horizon\Support\Path;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeViewCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Makes a new view file');

		$this->addArgument(
			'name',
			InputArgument::REQUIRED,
			'The name of the view file.'
		);

		$this->addArgument(
			'schematic',
			InputArgument::OPTIONAL,
			'The name of a starter schematic.'
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

		$targetFilePath = Path::resolve(Framework::path('app/views'), $name) . '.twig';
		$targetDirPath = Path::dirname($targetFilePath);
		$relativeFilePath = str_replace('\\', '/', substr($targetFilePath, strlen(Framework::path()) + 1));

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

		// Create the file
		$content = $this->getSchematic($in->getArgument('schematic'));
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
	 * Returns the schematic contents.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getSchematic($name) {
		if ($name !== null) {
			$name = strtolower(trim($name));
			$path = Path::resolve(Framework::path('horizon/resources/ace/make/views'), $name . '.twig');

			if (file_exists($path)) {
				return file_get_contents($path);
			}

			throw new RuntimeException("Unknown schematic \"$name\"");
		}

		return '';
	}

}
