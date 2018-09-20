<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Enum\File\FileOperationType;

class MakeFile extends Command
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
            throw new CommandException(sprintf('Expected %d arguments, got %d in makefile()', 1, count($args)));
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

        $this->registerFileOperation($this->relativeFilePath, FileOperationType::CREATE);
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $fileMode = substr(sprintf('%o', fileperms(__FILE__)), -4);
        $dirMode = substr(sprintf('%o', fileperms(__DIR__)), -4);

        $parentDir = dirname($this->absoluteFilePath);

        $this->getLogger()->info('Making a file at:', $this->relativeFilePath);
        $this->getLogger()->info('Resolved absolute path:', $this->absoluteFilePath);
        $this->getLogger()->info('Permission mode to set:', $fileMode);

        if (file_exists($this->absoluteFilePath)) {
            $this->getLogger()->info('The file already exists, skipping.');
            return;
        }

        if (!file_exists($parentDir)) {
            $this->getLogger()->info('The parent directory does not exist:', $parentDir);
            $this->getLogger()->info('Attempting to create it with a chmod of ' . $dirMode . '...');

            $make = @mkdir($parentDir, $dirMode, true);

            if ($mkdir === false) {
                $this->getLogger()->error('Failed to create the directory recursively.');
                throw new CommandException('Failed to create directory, is there a permission issue?');
            }

            $this->getLogger()->info('Directory created successfully.');
        }

        $written = @file_put_contents($this->absoluteFilePath, '');

        if ($written === false) {
            $this->getLogger()->error('Failed to create the file.');
            throw new CommandException('Failed to create file, is there a permission issue?');
        }

        $this->getLogger()->info('File created successfully.');
    }

}