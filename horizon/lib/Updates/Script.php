<?php

namespace Horizon\Updates;

use Horizon\Enum\File\FileOperationType;

class Script
{

    /**
     * @var Package
     */
    protected $package;

    /**
     * @var string
     */
    protected $rawText;

    /**
     * @var Command[]
     */
    protected $commands;

    /**
     * Constructs a new Script instance and loads the commands from a raw string.
     *
     * @param Package $package
     * @param string $raw
     */
    public function __construct(Package $package, $raw)
    {
        $this->package = $package;
        $this->rawText = $raw;

        $this->parse();
    }

    /**
     * Gets an array of commands in this script.
     *
     * @return Command[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Goes through commands which will run before the caller command, and finds the last occurrence of the specified
     * command classname. This is useful, for example, if we want to find the last 'TargetDirectory' command ran before
     * an extract call. Returns NULL if not found.
     *
     * @param Command $caller
     * @param string $className
     * @return Command|null
     */
    public function getLastCommandByType(Command $caller, $className)
    {
        $fullClassName = Command::getClassName($className);
        $last = null;

        if (is_null($fullClassName)) {
            return null;
        }

        foreach ($this->commands as $command) {
            if ($command == $caller) {
                break;
            }

            if ($command instanceof $fullClassName) {
                $last = $command;
            }
        }

        return $last;
    }

    /**
     * Gets the package instance for this script.
     *
     * @return Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Gets the version instance for this script.
     *
     * @return Version
     */
    public function getVersion()
    {
        return $this->package->getVersion();
    }

    /**
     * Gets the channel instance for this script.
     *
     * @return Channel
     */
    public function getChannel()
    {
        return $this->package->getChannel();
    }

    /**
     * Gets the repository instance for this script.
     *
     * @return Repository
     */
    public function getRepo()
    {
        return $this->package->getRepo();
    }

    /**
     * Parses the script into commands. Does nothing if already parsed.
     */
    protected function parse()
    {
        $rawText = str_replace("\r\n", "\n", $this->rawText);
        $lines = explode("\n", $rawText);

        $this->commands = array();

        foreach ($lines as $lineNumberZeroed => $line) {
            $lineNumber = $lineNumberZeroed + 1;
            $line = new ScriptLine($this, $lineNumber, $line);

            if (!$line->isEmpty()) {
                $commandName = $line->getCommand();
                $command = Command::create($commandName, $this, $line->getArgumentValues(), $line->isSilent());

                if (is_null($command)) {
                    throw new ScriptException(sprintf('Unknown command "%s" on line %d', $commandName, $lineNumber));
                }

                $this->commands[] = $command;
            }
        }
    }

    /**
     * Returns an array of file paths (relative to the mounted directory) which will be created, modified, or deleted
     * by the script.
     *
     * @return string[]
     */
    public function getTouchedFiles()
    {
        $files = array();

        foreach ($this->getChangedFiles() as $path) {
            if (!in_array($path, $files)) {
                $files[] = $path;
            }
        }

        foreach ($this->getCreatedFiles() as $path) {
            if (!in_array($path, $files)) {
                $files[] = $path;
            }
        }

        foreach ($this->getDeletedFiles() as $path) {
            if (!in_array($path, $files)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Returns an array of file paths (relative to the mounted directory) which should be backed up before running the
     * script, due to being deleted or overwritten.
     *
     * @return string[]
     */
    public function getFilesToBackUp()
    {
        $files = array();

        foreach ($this->getChangedFiles() as $path) {
            if (!in_array($path, $files)) {
                $files[] = $path;
            }
        }

        foreach ($this->getDeletedFiles() as $path) {
            if (!in_array($path, $files)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Returns an array of directory paths (relative to the mounted directory) which should be backed up before running the
     * script, due to being deleted.
     *
     * @return string[]
     */
    public function getDirectoriesToBackUp()
    {
        return $this->getDeletedDirectories();
    }

    /**
     * Returns an array of directory paths (relative to the mounted directory) which will be created, modified, or deleted
     * by the script.
     *
     * @return string[]
     */
    public function getTouchedDirectories()
    {
        $files = array();

        foreach ($this->getCreatedDirectories() as $path) {
            if (!in_array($path, $files)) {
                $files[] = $path;
            }
        }

        foreach ($this->getDeletedDirectories() as $path) {
            if (!in_array($path, $files)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Returns an array of file paths (relative to the mounted directory) which match the specified operation enum.
     *
     * @param int $mode
     * @return string[]
     */
    protected function getFilesByOperation($mode)
    {
        $files = array();

        foreach ($this->commands as $command) {
            foreach ($command->getFileOperations() as $operation) {
                if ($operation->type == $mode) {
                    if (!in_array($operation->path, $files)) {
                        $files[] = $operation->path;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Returns an array of directory paths (relative to the mounted directory) which match the specified operation enum.
     *
     * @param int $mode
     * @return string[]
     */
    protected function getDirectoriesByOperation($mode)
    {
        $files = array();

        foreach ($this->commands as $command) {
            foreach ($command->getDirectoryOperations() as $operation) {
                if ($operation->type == $mode) {
                    if (!in_array($operation->path, $files)) {
                        $files[] = $operation->path;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Returns an array of file paths (relative to the mounted directory) which will be created by the script.
     *
     * @return string[]
     */
    public function getCreatedFiles()
    {
        return $this->getFilesByOperation(FileOperationType::CREATE);
    }

    /**
     * Returns an array of file paths (relative to the mounted directory) which will be modified by the script.
     *
     * @return string[]
     */
    public function getChangedFiles()
    {
        return $this->getFilesByOperation(FileOperationType::MODIFY);
    }

    /**
     * Returns an array of file paths (relative to the mounted directory) which will be deleted by the script.
     *
     * @return string[]
     */
    public function getDeletedFiles()
    {
        return $this->getFilesByOperation(FileOperationType::DELETE);
    }

    /**
     * Returns an array of directory paths (relative to the mounted directory) which will be created by the script.
     *
     * @return string[]
     */
    public function getCreatedDirectories()
    {
        return $this->getDirectoriesByOperation(FileOperationType::CREATE);
    }

    /**
     * Returns an array of directory paths (relative to the mounted directory) which will be deleted by the script.
     *
     * @return string[]
     */
    public function getDeletedDirectories()
    {
        return $this->getDirectoriesByOperation(FileOperationType::DELETE);
    }

    /**
     * Executes the script.
     *
     * @throws CommandException
     */
    public function execute()
    {
        foreach ($this->getCommands() as $step => $command) {
            try {
                $command->execute();
            }
            catch (\Exception $e) {
                if ($command->isSilent()) {
                    UpdateService::getLogger()->error('An exception was encountered, but was ignored because the line was silenced.');
                    UpdateService::getLogger()->error('Exception:', $e->getMessage());
                }
                else {
                    throw $e;
                }
            }
        }
    }

}