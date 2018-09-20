<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Enum\File\FileOperationType;

class RemoveFile extends Command
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
            throw new CommandException(sprintf('Expected %d arguments, got %d in removefile()', 1, count($args)));
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

        $this->registerFileOperation($this->relativeFilePath, FileOperationType::DELETE);
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $this->getLogger()->info('Removing file at:', $this->absoluteFilePath);

        if (!file_exists($this->absoluteFilePath)) {
            $this->getLogger()->info('The target does not exist, skipping.');
            return;
        }

        $success = @unlink($this->absoluteFilePath);

        if (!$success) {
            $this->getLogger()->error('Could not delete file at', $this->absoluteFilePath);
            throw new CommandException('Failed to delete file');
        }

        $this->getLogger()->info('File deleted successfully.');
    }

}