<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;

class VerifyFileMode extends Command
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
     * @var int
     */
    protected $chmod;

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) !== 2) {
            throw new CommandException(sprintf('Expected %d arguments, got %d in VerifyFileMode()', 2, count($args)));
        }

        $this->relativeFilePath = $this->getRepo()->toRelativePath($args[0]);
        $this->absoluteFilePath = $this->getRepo()->toAbsolutePath($args[0]);
        $this->chmod = $args[1];
    }

    /**
     * Validates the data parsed from arguments.
     */
    protected function validate()
    {
        if (!$this->getRepo()->isPathMounted($this->absoluteFilePath)) {
            throw new CommandException(sprintf('Attempted to access file "%s" which is outside of the mounted directory', $this->relativeFilePath));
        }

        if (!is_octal($this->chmod)) {
            throw new CommandException(sprintf('Invalid octal value "%s" in VerifyFileMode()', $this->chmod));
        }
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $this->getLogger()->info('Verifying permissions at', $this->absoluteFilePath, 'equal to', $this->chmod);

        if (!file_exists($this->absoluteFilePath)) {
            $this->getLogger()->info('File or directory not found, skipping.');
            return;
        }

        $actualMode = substr(sprintf('%o', fileperms($this->absoluteFilePath)), -4);
        $this->getLogger()->info('Calculated permissions at', $actualMode);

        if ($actualMode != $this->chmod) {
            $this->getLogger()->error('The permissions do not match.');
            throw new CommandException('Permission mismatch');
        }

        $this->getLogger()->info('OK.');
    }

}