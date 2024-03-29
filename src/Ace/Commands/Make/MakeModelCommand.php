<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Ace\Util\FileGenerator;
use Horizon\Ace\Util\FileOpener;
use Horizon\Console\Command;
use Horizon\Foundation\Application;
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
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the view file.');
		$this->addOption('root', 'r', InputOption::VALUE_NONE, 'Uses the root source directory.');
		$this->addOption('open', 'o', InputOption::VALUE_NONE, 'Opens the file with your default PHP editor.');
		$this->addOption('timestamps', 't', InputOption::VALUE_NONE, 'Adds timestamps to the model.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$generator = new FileGenerator($in->getArgument('name'));
		$generator->namespace = $in->getOption('root') ? 'App' : 'App/Database/Models';
		$generator->baseDir = Application::paths()->srcDir($in->getOption('root') ? '' : 'Database/Models');

		$generator->assertClassName(['Model']);
		$generator->renderClassFile('make/model', [
			'table' => $this->getTableName($generator->getClassName()),
			'withTimestamps' => !!$in->getOption('timestamps')
		], $out);

		if ($in->getOption('open')) {
			FileOpener::open($generator->resolveClassPath());
		}
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
