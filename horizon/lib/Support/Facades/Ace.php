<?php

namespace Horizon\Support\Facades;

use Horizon\Foundation\Application;
use Symfony\Component\Console\Output\OutputInterface;

class Ace {

	/**
	 * Executes the given command on the internal command line tool with the specified arguments array. Errors will
	 * not be caught. The exit code is returned, and output is sent directly to stdout unless an output stream is
	 * provided in the third argument.
	 *
	 * @param string $command
	 * @param string[] $args
	 * @param OutputInterface|null $output
	 * @return void
	 */
	public static function run($command, $args = array(), OutputInterface $output = null) {
		return Application::kernel()->console()->execute(array_merge(
			[$command],
			$args
		), $output);
	}

}
