<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;

class ExecuteMatch extends Command
{

    /**
     * @var mixed
     */
    protected $shouldReturn = null;

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) !== 1) {
            throw new CommandException(sprintf('Expected %d argument, got %d in ExecuteMatch()', 1, count($args)));
        }

        $execution = $this->getScript()->getLastCommandByType($this, 'ExecuteFile');

        if (is_null($execution)) {
            throw new CommandException('Illegal attempt to check file execution response');
        }

        $this->shouldReturn = $args[0];
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $runner = $this->getScript()->getLastCommandByType($this, 'ExecuteFile');

        $this->getLogger()->info('Checking if the previous file execution was successful.');

        if ($runner->getError() !== null) {
            $this->getLogger()->error('The execution encountered an error:', $runner->getError()->getMessage());
            throw new CommandException('File execution error: ' . $runner->getError()->getMessage());
        }
        else {
            $this->getLogger()->info('The execution was successful.');
        }

        if ($this->shouldReturn === 'ok') {
            return;
        }

        $returned = $runner->getReturned();

        $this->getLogger()->info('The execution should have returned:', $this->shouldReturn);
        $this->getLogger()->info('The execution actually returned:', $returned);

        if ($returned != $this->shouldReturn) {
            $this->getLogger()->error('Loose match failed.');
            throw new CommandException('File execution returned an unexpected value');
        }

        $this->getLogger()->info('OK.');
    }

}