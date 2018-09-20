<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Enum\File\FileOperationType;
use Horizon\Utils\Path;
use Horizon\Utils\ZipArchive;
use Horizon\Utils\Str;

class Extract extends Command
{

    /**
     * @var string
     */
    protected $scope = '';

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) > 0) {
            throw new CommandException(sprintf('Expected %d arguments, got %d in Extract()', 0, count($args)));
        }

        $target = $this->getScript()->getLastCommandByType($this, 'TargetDirectory');

        if (!is_null($target)) {
            $this->scope = ltrim($target->getTarget() . '/', '/');
        }
    }

    /**
     * Validates the data parsed from arguments.
     */
    protected function validate()
    {
        $archive = $this->getStagedArchive();

        foreach($this->getStagedFiles() as $file) {
            $relativePath = $file['path'];
            $absolutePath = $this->getRepo()->toAbsolutePath($relativePath);
            $exists = file_exists($absolutePath);

            $type = ($exists) ? FileOperationType::MODIFY : FileOperationType::CREATE;
            $this->registerFileOperation($relativePath, $type);
        }

        foreach($this->getStagedDirectories() as $relativePath) {
            $absolutePath = $this->getRepo()->toAbsolutePath($relativePath);
            $exists = file_exists($absolutePath);

            if (!$exists) {
                $this->registerDirectoryOperation($relativePath, FileOperationType::CREATE);
            }
        }
    }

    /**
     * @return ZipArchive
     */
    protected function getStagedArchive()
    {
        $stage = $this->getScript()->getLastCommandByType($this, 'StageArchive');

        if (is_null($stage)) {
            throw new CommandException('Cannot extract files without a staged archive');
        }

        return $stage->getZipArchive();
    }

    /**
     * @return string[]
     */
    protected function getStagedFiles()
    {
        $files = array();
        $archive = $this->getStagedArchive();

        foreach($archive->getFiles() as $file) {
            $dir = ltrim($file['dir'] . '/', '/');

            if (empty($this->scope) || Str::startsWith($dir, $this->scope)) {
                $filePath = $dir . $file['name'];

                if (!empty($this->scope)) {
                    $filePath = substr($filePath, strlen($this->scope));
                }

                $files[] = array('path' => $filePath, 'data' => $file['data']);
            }
        }

        return $files;
    }

    /**
     * @return string[]
     */
    protected function getStagedDirectories()
    {
        $files = array();
        $archive = $this->getStagedArchive();

        foreach($archive->getFiles() as $file) {
            $dir = trim($file['dir'] . '/', '/');

            if (empty($dir)) {
                continue;
            }

            if (empty($this->scope) || Str::startsWith($dir, $this->scope)) {
                if (!in_array($dir, $files)) {
                    $files[] = $dir;
                }
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

        $fileMode = substr(sprintf('%o', fileperms(__FILE__)), -4);
        $dirMode = substr(sprintf('%o', fileperms(__DIR__)), -4);

        $this->getLogger()->info('Starting compressed payload extraction...');
        $this->getLogger()->info('Encryption:', ($this->getStagedArchive()->isEncrypted() ? 'Yes' : 'No'));
        $this->getLogger()->info('File mode:', $fileMode);
        $this->getLogger()->info('Directory mode:', $dirMode);

        // Create new directories
        foreach ($this->getStagedDirectories() as $relativePath) {
            $absolutePath = $this->getRepo()->toAbsolutePath($relativePath);

            $this->getLogger()->info('Extracting staged innode:', $relativePath);
            $this->getLogger()->info('Extracting to directory:', $absolutePath);

            $this->extractDirectory($absolutePath, $dirMode);
        }

        // Create new files
        foreach ($this->getStagedFiles() as $file) {
            $idPath = $this->getVersion()->getId() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['path']);
            $relativePath = $file['path'];
            $absolutePath = $this->getRepo()->toAbsolutePath($relativePath);

            $this->getLogger()->info('Extracting staged innode:', $idPath);
            $this->getLogger()->info('Extracting to file:', $absolutePath);

            $this->extractFile($absolutePath, $fileMode, $file['data']);
        }
    }

    /**
     * Extracts the specified directory.
     *
     * @param string $absolutePath
     * @param string $dirMode
     * @throws CommandException
     */
    protected function extractDirectory($absolutePath, $dirMode)
    {
        if (file_exists($absolutePath)) {
            return;
        }

        if (!@mkdir($absolutePath, $dirMode, true)) {
            $this->getLogger()->error('Directory creation failed for an unknown reason.');
            $this->getLogger()->error('Most likely, the directory name is invalid, the path is too long, or there is a permission error.');

            throw new CommandException(sprintf('Failed to create recursive directory at %s', $absolutePath));
        }

        $this->getLogger()->info('Success, directory created.');
    }

    /**
     * Extracts the specified file.
     *
     * @param string $absolutePath
     * @param string $fileMode
     * @param string $data
     * @throws CommandException
     */
    protected function extractFile($absolutePath, $fileMode, $data)
    {
        $directory = dirname($absolutePath);

        if (!file_exists($directory)) {
            $this->extractDirectory($directory);
        }

        $bytes = @file_put_contents($absolutePath, $data);

        if ($bytes === false) {
            $this->getLogger()->error('File creation failed for an unknown reason.');
            $this->getLogger()->error('Most likely, the file name is invalid, the path is too long, or there is a permission error.');

            throw new CommandException(sprintf('Failed to create file at %s', $absolutePath));
        }

        $this->getLogger()->info('Success, wrote', number_format($bytes), 'bytes');

        @chmod($absolutePath, $fileMode);
    }

}