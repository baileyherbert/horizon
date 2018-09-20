<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;

class ChangeFileMode extends Command
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
            throw new CommandException(sprintf('Expected %d arguments, got %d in ChangeFileMode()', 2, count($args)));
        }

        $this->relativeFilePath = $this->getRepo()->toRelativePath($args[0]);
        $this->absoluteFilePath = $this->getRepo()->toAbsolutePath($args[0]);
        $this->chmod = (isset($args[1])) ? $args[1] : 0777;
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
            throw new CommandException(sprintf('Invalid octal value "%s" in ChangeFileMode()', $this->chmod));
        }
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $this->getLogger()->info('Changing permissions to', $this->chmod, 'at:', $this->absoluteFilePath);

        if (!file_exists($this->absoluteFilePath)) {
            $this->getLogger()->info('The target does not exist, skipping.');
            return;
        }

        $success = @chmod($this->absoluteFilePath, $this->chmod);

        if (!$success) {
            $this->getLogger()->error('Could not chmod to', $this->chmod, 'at', $this->absoluteFilePath);
            throw new CommandException('Failed to set file permissions');
        }

        $this->getLogger()->info('Permissions changed successfully.');
    }

}