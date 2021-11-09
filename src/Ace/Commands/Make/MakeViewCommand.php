<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Ace\Util\FileGenerator;
use Horizon\Console\Command;
use Horizon\Foundation\Application;
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
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the view file.');
		$this->addArgument('schematic', InputArgument::OPTIONAL, 'The name of a starter schematic.');
		$this->addOption('open', 'o', InputOption::VALUE_NONE, 'Opens the file with your default PHP editor.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$generator = new FileGenerator($in->getArgument('name'));
		$generator->baseDir = Application::paths()->views();
		$generator->extension = 'twig';
		$generator->writeFile($this->getSchematic($in->getArgument('schematic')), $out);

		if ($in->getOption('open')) {
			exec('start ' . $generator->resolveFilePath());
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
			$path = Path::resolve(Framework::path('resources/ace/make/views'), $name . '.twig');

			if (file_exists($path)) {
				return file_get_contents($path);
			}

			throw new RuntimeException("Unknown schematic \"$name\"");
		}

		return '';
	}

}
