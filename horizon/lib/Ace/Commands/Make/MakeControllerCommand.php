<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Ace\Util\FileGenerator;
use Horizon\Console\Command;
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
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the controller.');
		$this->addOption('root', 'r', InputOption::VALUE_NONE, 'Uses the root source directory.');
		$this->addOption('open', 'o', InputOption::VALUE_NONE, 'Opens the file with your default PHP editor.');

		$this->addOption('post', null, InputOption::VALUE_NONE, 'Adds a POST route method.');
		$this->addOption('delete', null, InputOption::VALUE_NONE, 'Adds a DELETE route method.');
		$this->addOption('put', null, InputOption::VALUE_NONE, 'Adds a PUT route method.');
		$this->addOption('patch', null, InputOption::VALUE_NONE, 'Adds a PATCH route method.');
		$this->addOption('blank', null, InputOption::VALUE_NONE, 'Skips adding route methods.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$generator = new FileGenerator($in->getArgument('name'));
		$generator->namespace = $in->getOption('root') ? 'App' : 'App/Http/Controllers';
		$generator->baseDir = $in->getOption('root') ? 'app/src' : 'app/src/Http/Controllers';
		$generator->classNameSuffix = 'Controller';

		$generator->assertClassName(['Controller', 'Request', 'Response']);
		$generator->renderClassFile('make/controller', $this->getRouteMethods($in), $out);

		if ($in->getOption('open')) {
			exec('start ' . $generator->resolveClassPath());
		}
	}

	/**
	 * Returns an array of route methods to include in the generated template.
	 *
	 * @param InputInterface $in
	 * @return string[]
	 */
	protected function getRouteMethods(InputInterface $in) {
		$methods = ['get' => true];

		if ($in->getOption('blank')) return [];
		if ($in->getOption('post')) $methods['post'] = true;
		if ($in->getOption('put')) $methods['put'] = true;
		if ($in->getOption('patch')) $methods['patch'] = true;
		if ($in->getOption('delete')) $methods['delete'] = true;

		return $methods;
	}

}
