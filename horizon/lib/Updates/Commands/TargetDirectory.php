<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;

class TargetDirectory extends Command
{

    /**
     * @var string
     */
    protected $target;

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) !== 1) {
            throw new CommandException(sprintf('Expected %d arguments, got %d in TargetDirectory()', 1, count($args)));
        }

        $this->target = trim($args[0], '/');
    }

    /**
     * Gets the relative path in the archive to extract from.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Executes the command.
     */
    public function execute()
    {
        $this->getLogger()->info('Set extraction target to:', '/' . $this->getTarget());
    }

}