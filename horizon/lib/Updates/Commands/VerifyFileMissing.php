<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;

class VerifyFileMissing extends Command
{

    /**
     * @var string
     */
    protected $relativeFilePath;

    /**
     * @var string
     */
    protected $absoluteFilePath;

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) !== 1) {
            throw new CommandException(sprintf('Expected %d arguments, got %d in VerifyFileMissing()', 1, count($args)));
        }

        $this->relativeFilePath = $this->getRepo()->toRelativePath($args[0]);
        $this->absoluteFilePath = $this->getRepo()->toAbsolutePath($args[0]);
    }

    /**
     * Validates the data parsed from arguments.
     */
    protected function validate()
    {
        if (!$this->getRepo()->isPathMounted($this->absoluteFilePath)) {
            throw new CommandException(sprintf('Attempted to access file "%s" which is outside of the mounted directory', $this->relativeFilePath));
        }
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $this->getLogger()->info('Verifying nonexistence of', $this->absoluteFilePath);

        if (file_exists($this->absoluteFilePath)) {
            $this->getLogger()->error('File or directory does exist.');
            throw new CommandException('Illegal file entry');
        }

        $this->getLogger()->info('OK.');
    }

}