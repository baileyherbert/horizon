<?php

namespace Horizon\Console;

use Horizon\Foundation\Framework;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Kernel for console applications.
 */
class Kernel
{

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
    public function boot()
    {
        $this->initConsoleApp();
        $this->initCommands();
        $this->runConsoleApp();
    }

    public function inject(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @return InputInterface
     */
    public function input()
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function output()
    {
        return $this->output;
    }

    private function initConsoleApp()
    {
        $this->consoleApp = new Application();
        $this->consoleApp->setName(config('console.name', 'Horizon'));
        $this->consoleApp->setVersion(config('console.version', Framework::version()));
    }

    private function initCommands()
    {
        $commands = config('console.commands', array());

        foreach ($commands as $key => $className) {
            if (class_exists($className)) {
                $this->consoleApp->add(new $className($key));
            }
        }
    }

    private function runConsoleApp()
    {
        try {
            $output = new ConsoleOutput();

            $this->consoleApp->setAutoExit(false);
            $this->consoleApp->setCatchExceptions(false);
            $code = $this->consoleApp->run(null, $output);

            // Write an extra line at the end for powershell (it removes the last line from stdout)
            if (env('PSModulePath')) {
                $output->writeln('');
            }

            abort($code);
        }
        catch (\Exception $ex) {
            $this->handleException($ex);
        }
    }

    /**
     * Handles the given exception from within a console command.
     *
     * @param Exception|Error $ex
     * @return void
     */
    public function handleException($ex) {
        if (!($ex instanceof RuntimeException)) {
            $error = HorizonError::fromException($ex);
            $handler = ErrorMiddleware::getErrorHandler();

            if (config('errors.console_logging', true)) {
                $handler->log($error);
            }

            if (config('errors.console_reporting', true)) {
                $handler->report($error);
            }

            $this->consoleApp->renderException($e, $output);
        }

        $this->consoleApp->renderException($ex, $this->output);
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
