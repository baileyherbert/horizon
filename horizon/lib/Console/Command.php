<?php

namespace Horizon\Console;

use Exception;
use Horizon\Foundation\Application;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Horizon\Foundation\Kernel;

class Command extends SymfonyCommand
{

	/**
	 * Runs the command. This expects the full console environment to be configured. Do not call this manually.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws Exception
	 */
	public function run(InputInterface $input, OutputInterface $output)
	{
		Application::kernel()->console()->inject($input, $output);
		return parent::run($input, $output);
	}

	/**
	 * Gets the input interface for the console.
	 *
	 * @return InputInterface
	 */
	protected function getInput()
	{
		return Application::kernel()->console()->input();
	}

	/**
	 * Gets the output interface for the console.
	 *
	 * @return OutputInterface
	 */
	protected function getOutput()
	{
		return Application::kernel()->console()->output();
	}

}
