<?php

namespace Horizon\Console;

use Horizon;
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
        $this->consoleApp->setVersion(config('console.version', Horizon::VERSION));
    }

    private function initCommands()
    {
        $commands = config('console.commands', array());

        foreach ($commands as $name) {
            if (class_exists($name)) {
                $this->consoleApp->add(new $name());
            }
        }
    }

    private function runConsoleApp()
    {
        $this->consoleApp->run();
    }

}
