<?php

namespace Horizon\Updates;

use Horizon\Logging\Logger;

abstract class Command
{

    /**
     * @var array
     */
    protected $args = array();

    /**
     * @var Script
     */
    protected $script;

    /**
     * @var array
     */
    protected $fileOperations = array();

    /**
     * @var array
     */
    protected $dirOperations = array();

    /**
     * @var bool
     */
    protected $silent = false;

    /**
     * Constructs a new Command instance.
     *
     * @param array $args
     */
    public function __construct(Script $script, array $args, $silent = false)
    {
        $this->script = $script;
        $this->args = $args;
        $this->silent = $silent;

        $this->parse($args);
        $this->validate();
    }

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {

    }

    /**
     * Validates the data parsed from arguments.
     */
    protected function validate()
    {

    }

    /**
     * Gets the script instance for this command.
     *
     * @return Script
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * Gets the package instance for this command.
     *
     * @return Package
     */
    public function getPackage()
    {
        return $this->script->getPackage();
    }

    /**
     * Gets the version instance for this command.
     *
     * @return Version
     */
    public function getVersion()
    {
        return $this->script->getVersion();
    }

    /**
     * Gets the channel instance for this command.
     *
     * @return Channel
     */
    public function getChannel()
    {
        return $this->script->getChannel();
    }

    /**
     * Gets the repository instance for this command.
     *
     * @return Repository
     */
    public function getRepo()
    {
        return $this->script->getRepo();
    }

    /**
     * Gets the logger instance from the update service.
     *
     * @return Logger
     */
    public function getLogger()
    {
        return UpdateService::getLogger();
    }

    /**
     * Registers a file operation for the command on the specified file.
     *
     * @param string $relativeFilePath
     * @param int $operationType
     */
    protected function registerFileOperation($relativeFilePath, $operationType)
    {
        $this->fileOperations[] = (object) array(
            'path' => $relativeFilePath,
            'type' => $operationType
        );
    }

    /**
     * Registers a directory operation for the command on the specified directory path.
     *
     * @param string $relativeFilePath
     * @param int $operationType
     */
    protected function registerDirectoryOperation($relativeFilePath, $operationType)
    {
        $this->dirOperations[] = (object) array(
            'path' => $relativeFilePath,
            'type' => $operationType
        );
    }

    /**
     * Checks if there is a command with the specified name.
     *
     * @param string $commandName
     * @return bool
     */
    public static function exists($commandName)
    {
        $className = static::getClassName($commandName);

        if (is_null($className)) {
            return false;
        }

        return class_exists($className);
    }

    /**
     * Checks if there is a command with the specified name.
     *
     * @param string $commandName
     * @param Script $script
     * @param array $args
     * @return Command|null
     */
    public static function create($commandName, Script $script, array $args, $silent = false)
    {
        $className = static::getClassName($commandName);

        if (is_null($className)) {
            return null;
        }

        if (!class_exists($className)) {
            return null;
        }

        return new $className($script, $args, $silent);
    }

    /**
     * Gets the name of the class for the specified command, or null if the command is not valid.
     *
     * @param string $commandName
     * @return null
     */
    public static function getClassName($commandName)
    {
        $commandName = trim($commandName);
        $className = 'Horizon\\Updates\\Commands\\' . $commandName;

        if (class_exists($className)) {
            return $className;
        }

        foreach (static::getCommandMap() as $command => $classPartialName) {
            if (strcasecmp($commandName, $command) == 0) {
                return 'Horizon\\Updates\\Commands\\' . $classPartialName;
            }
        }

        return null;
    }

    /**
     * Gets an array of commands and the associated command class name.
     *
     * @return array
     */
    protected static function &getCommandMap()
    {
        static $map = array(
            'stage' => 'StageArchive',
            'target' => 'TargetDirectory',
            'scope' => 'TargetDirectory',
            'select' => 'TargetDirectory',
            'extract' => 'Extract',
            'gextract' => 'ExtractGraceful',
            'graceful' => 'ExtractGraceful',
            'query' => 'Query',
            'squery' => 'QuerySilent',
            'execute' => 'ExecuteFile',
            'executed' => 'ExecuteMatch',
            'mkdir' => 'MakeDirectory',
            'touch' => 'MakeFile',
            'chmod' => 'ChangeFileMode',
            'rm' => 'RemoveFile',
            'rmdir' => 'RemoveDirectory',
            'vchmod' => 'VerifyFileMode',
            'vexists' => 'VerifyFileExists',
            'vdeleted' => 'VerifyFileMissing'
        );

        return $map;
    }

    /**
     * Gets the command's arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->args;
    }

    /**
     * Executes the command.
     *
     * @throws CommandException
     */
    public function execute()
    {
        throw new CommandException('Command not implemented');
    }

    /**
     * Gets an array of file operations. Each operation is an object containing a 'path' for the file, relative to the
     * root directory, and a 'type', an integer from the FileOperationType enum.
     *
     * @return array
     */
    public function getFileOperations()
    {
        return $this->fileOperations;
    }

    /**
     * Gets an array of directory operations. Each operation is an object containing a 'path' for the directory,
     * relative to the root directory, and a 'type', an integer from the FileOperationType enum.
     *
     * @return array
     */
    public function getDirectoryOperations()
    {
        return $this->dirOperations;
    }

    /**
     * Gets whether or not this command is silenced (should ignore errors).
     *
     * @return bool
     */
    public function isSilent()
    {
        return $this->silent;
    }

}
