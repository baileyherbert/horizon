<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Enum\File\FileOperationType;

class MakeDirectory extends Command
{

    /**
     * @var string
     */
    protected $relativeDirectoryPath;

    /**
     * @var string
     */
    protected $absoluteDirectoryPath;

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
        if (count($args) < 1 || count($args) > 2) {
            throw new CommandException(sprintf('Expected %d arguments, got %d in makedirectory()', '1 or 2', count($args)));
        }

        $this->relativeDirectoryPath = $this->getRepo()->toRelativePath($args[0]);
        $this->absoluteDirectoryPath = $this->getRepo()->toAbsolutePath($args[0]);
        $this->chmod = (isset($args[1])) ? $args[1] : 0777;
    }

    /**
     * Validates the data parsed from arguments.
     */
    protected function validate()
    {
        if (!$this->getRepo()->isPathMounted($this->absoluteDirectoryPath)) {
            throw new CommandException(sprintf('Attempted to access directory "%s" which is outside of the mounted directory', $this->relativeDirectoryPath));
        }

        if (!is_octal($this->chmod)) {
            throw new CommandException(sprintf('Invalid octal value "%s" in makedirectory()', $this->chmod));
        }

        $this->registerDirectoryOperation($this->relativeDirectoryPath, FileOperationType::CREATE);
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $this->getLogger()->info('Making a directory at:', $this->relativeDirectoryPath);
        $this->getLogger()->info('Resolved absolute path:', $this->absoluteDirectoryPath);
        $this->getLogger()->info('Permission mode to set:', $this->chmod);

        if (file_exists($this->absoluteDirectoryPath)) {
            $this->getLogger()->info('The directory already exists, skipping.');
            return;
        }

        $make = @mkdir($this->absoluteDirectoryPath, $this->chmod, true);

        if ($mkdir === false) {
            $this->getLogger()->error('Failed to create the directory recursively.');
            throw new CommandException('Failed to create directory, is there a permission issue?');
        }

        $this->getLogger()->info('Created successfully.');
    }

}