<?php

namespace Horizon\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Horizon\Framework\Kernel;

class Command extends SymfonyCommand
{

    /**
     * Runs the command. This expects the full console environment to be configured. Do not call this manually.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        Kernel::__initCommand($input, $output);
        return parent::run($input, $output);
    }

    /**
     * Gets the input interface for the console.
     *
     * @return InputInterface
     */
    protected function getInput()
    {
        return Kernel::getConsoleInput();
    }

    /**
     * Gets the output interface for the console.
     *
     * @return OutputInterface
     */
    protected function getOutput()
    {
        return Kernel::getConsoleOutput();
    }

}
