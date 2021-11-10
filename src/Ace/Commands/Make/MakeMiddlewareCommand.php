<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Ace\Util\FileGenerator;
use Horizon\Console\Command;
use Horizon\Foundation\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Makes a new middleware file');
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware.');
		$this->addOption('root', 'r', InputOption::VALUE_NONE, 'Uses the root source directory.');
		$this->addOption('open', 'o', InputOption::VALUE_NONE, 'Opens the file with your default PHP editor.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$generator = new FileGenerator($in->getArgument('name'));
		$generator->namespace = $in->getOption('root') ? 'App' : 'App/Http/Middleware';
		$generator->baseDir = Application::paths()->src($in->getOption('root') ? '' : 'Http/Middleware');
		$generator->classNameSuffix = 'Middleware';

		$generator->assertClassName(['Middleware', 'Request', 'Response']);
		$generator->renderClassFile('make/middleware', [], $out);

		if ($in->getOption('open')) {
			exec('start ' . $generator->resolveClassPath());
		}
	}

}
