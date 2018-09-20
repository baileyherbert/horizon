<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Enum\File\FileOperationType;

class RemoveDirectory extends Command
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
     * @var array
     */
    protected $filesInside = array();

    /**
     * @var array
     */
    protected $dirsInside = array();

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) < 1 || count($args) > 2) {
            throw new CommandException(sprintf('Expected %d arguments, got %d in removedirectory()', '1 or 2', count($args)));
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
            throw new CommandException(sprintf('Attempted to access directory "%s" which is outside of the mounted directory', $relativeFilePath));
        }

        $this->registerDirectoryOperation($this->relativeDirectoryPath, FileOperationType::DELETE);
        $this->scan();
    }

    /**
     * Scans the directory for all files inside, and registers them as deletion operations.
     */
    protected function scan()
    {
        if (!file_exists($this->absoluteDirectoryPath)) {
            return;
        }

        $dirIterator = new \RecursiveDirectoryIterator($this->absoluteDirectoryPath);
        $fileIterator = new \RecursiveIteratorIterator($dirIterator);

        $files = array();

        foreach ($fileIterator as $file) {
            $path = $this->getRepo()->toRelativePath($file->getPathname());

            if ($file->getFilename() == '..') {
                continue;
            }

            if ($file->getFilename() == '.') {
                $path = substr($path, 0, -2);

                if ($path == $this->relativeDirectoryPath) {
                    continue;
                }

                $this->dirsInside[] = $this->getRepo()->toAbsolutePath($path);
                $this->registerDirectoryOperation($path, FileOperationType::DELETE);
                continue;
            }

            if ($file->isDir()) {
                $this->dirsInside[] = $this->getRepo()->toAbsolutePath($path);
                $this->registerDirectoryOperation($path, FileOperationType::DELETE);
            }
            else {
                $this->registerFileOperation($path, FileOperationType::DELETE);
                $this->filesInside[] = $this->getRepo()->toAbsolutePath($path);
            }
        }

        return $files;
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        $this->getLogger()->info('Deleting directory at:', $this->absoluteDirectoryPath);

        if (count($this->filesInside) > 0) {
            $this->getLogger()->warn('Recursively deleting', count($this->filesInside), 'file(s) and', count($this->dirsInside), 'folder(s) inside it.');
        }

        foreach ($this->filesInside as $path) {
            $this->getLogger()->info('Deleting subfile at:', $path);
            $success = @unlink($path);

            if (!$success) {
                $this->getLogger()->error('Could not delete subfile at', $path);
                throw new CommandException('Failed to delete directory');
            }
        }

        for ($i = count($this->dirsInside) - 1; $i >= 0; $i--) {
            $path = $this->dirsInside[$i];
            $this->getLogger()->info('Deleting subdirectory at:', $path);

            $success = @rmdir($path);

            if (!$success) {
                $this->getLogger()->error('Could not delete subdirectory at', $path);
                throw new CommandException('Failed to delete directory');
            }
        }

        $success = @rmdir($this->absoluteDirectoryPath);

        if (!$success) {
            $this->getLogger()->error('Could not delete directory at', $this->absoluteDirectoryPath);
            throw new CommandException('Failed to delete directory');
        }

        $this->getLogger()->info('Deleted successfully.');
    }

}