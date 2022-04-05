<?php

namespace Horizon\Console;

use Exception;
use Horizon\Exception\ErrorMiddleware;
use Horizon\Exception\HorizonError;
use Horizon\Foundation\Framework;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Kernel for console applications.
 */
class Kernel {

	/**
	 * @var Application
	 */
	private $consoleApp;

	/**
	 * @var InputInterface
	 */
	private $input;

	/**
	 * @var OutputInterface
	 */
	private $output;

	/**
	 * Boots the console kernel.
	 */
	public function boot() {
		$this->initConsoleApp();
		$this->initCommands();
		$this->runConsoleApp();
	}

	public function inject(InputInterface $input, OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
	}

	/**
	 * @return InputInterface
	 */
	public function input() {
		return $this->input;
	}

	/**
	 * @return OutputInterface
	 */
	public function output() {
		return $this->output;
	}

	private function initConsoleApp() {
		$this->consoleApp = new Application();
		$this->consoleApp->setName(config('console.name', 'Horizon'));
		$this->consoleApp->setVersion(config('console.version', Framework::version()));
	}

	private function initCommands() {
		$commands = config('console.commands', array());
		$developmentCommands = is_mode('development') ? [
			'build' => 'Horizon\Ace\Commands\Core\BuildCommand',
			'make:command' => 'Horizon\Ace\Commands\Make\MakeCommandCommand',
			'make:component' => 'Horizon\Ace\Commands\Make\MakeComponentCommand',
			'make:controller' => 'Horizon\Ace\Commands\Make\MakeControllerCommand',
			'make:middleware' => 'Horizon\Ace\Commands\Make\MakeMiddlewareCommand',
			'make:migration' => 'Horizon\Ace\Commands\Make\MakeMigrationCommand',
			'make:model' => 'Horizon\Ace\Commands\Make\MakeModelCommand',
			'make:view' => 'Horizon\Ace\Commands\Make\MakeViewCommand',
		] : [];

		$productionCommands = is_mode('production') ? [] : [];

		$commands = array_merge($commands, $developmentCommands, $productionCommands, array(
			'migration:fresh' => 'Horizon\Ace\Commands\Migrations\MigrationFreshCommand',
			'migration:rollback' => 'Horizon\Ace\Commands\Migrations\MigrationRollbackCommand',
			'migration:run' => 'Horizon\Ace\Commands\Migrations\MigrationRunCommand',
			'migration:status' => 'Horizon\Ace\Commands\Migrations\MigrationStatusCommand',
		));

		foreach ($commands as $key => $className) {
			if (class_exists($className)) {
				$this->consoleApp->add(new $className($key));
			}
		}
	}

	private function runConsoleApp() {
		try {
			$output = $this->output = new ConsoleOutput();

			$this->consoleApp->setAutoExit(false);
			$this->consoleApp->setCatchExceptions(false);
			$code = $this->consoleApp->run(null, $output);

			$this->exit($code);
		}
		catch (\Exception $ex) {
			$this->handleException($ex);
		}
	}

	/**
	 * Exits the application.
	 *
	 * @param int $code
	 * @return void
	 */
	public function exit($code = 0) {
		// Write an extra line at the end for powershell (it removes the last line from stdout)
		if (env('PSModulePath') && $this->output != null) {
			$this->output->writeln('');
		}

		abort($code);
	}

	/**
	 * Executes the given command on the internal command line tool with the specified arguments array. Errors will
	 * not be caught. The exit code is returned.
	 *
	 * @param string[] $args
	 * @param OutputInterface|null $output
	 * @return int
	 */
	public function execute(array $args, OutputInterface $output = null) {
		$input = new ArrayInput($args);
		$output = $output ?: new ConsoleOutput();

		if (!isset($this->consoleApp)) {
			$this->initConsoleApp();
			$this->initCommands();
		}

		$this->consoleApp->setAutoExit(false);
		$this->consoleApp->setCatchExceptions(false);
		return $this->consoleApp->run($input, $output);
	}

	/**
	 * Returns the current console app (if we are running in a console context) or null otherwise.
	 *
	 * @return Application|null
	 */
	public function getConsoleApp() {
		return $this->consoleApp;
	}

	/**
	 * Handles the given exception from within a console command.
	 *
	 * @param Exception|Error $ex
	 * @return void
	 */
	public function handleException($ex) {
		if (!($ex instanceof RuntimeException) && is_mode('production')) {
			$error = HorizonError::fromException($ex);
			$handler = ErrorMiddleware::getErrorHandler();

			if (config('errors.console_logging', true)) {
				$handler->log($error);
			}

			if (config('errors.console_reporting', true)) {
				$handler->report($error);
			}
		}

		$this->output->writeln(sprintf('<error>  %s: %s  </>', basename(get_class($ex)), $ex->getMessage()));
		$this->output->writeln(sprintf('<error>  @ %s:%d  </>', $ex->getFile(), $ex->getLine()));

		foreach ($ex->getTrace() as $index => $row) {
			$this->output->writeln(sprintf('  %d %s:%d - %s()', $index + 1, $row['file'], $row['line'], $row['function']));
		}

		// $this->consoleApp->renderException($ex, $this->output);
		abort($this->getExitCodeForThrowable($ex));
	}

	/**
	 * @param \Exception|\Throwable $throwable
	 * @return int
	 */
	private function getExitCodeForThrowable($throwable) {
		$exitCode = $throwable->getCode();

		if (is_numeric($exitCode)) {
			$exitCode = (int) $exitCode;

			if (0 === $exitCode) {
				$exitCode = 1;
			}
		}
		else {
			$exitCode = 1;
		}

		return $exitCode;
	}
}
