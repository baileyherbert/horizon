<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Enum\File\FileOperationType;

class ExtractGraceful extends Extract
{

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

            if (!$exists) {
                $this->registerFileOperation($relativePath, FileOperationType::CREATE);
            }
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
     * Extracts the specified file.
     *
     * @param string $absolutePath
     * @param string $fileMode
     * @param string $data
     * @throws CommandException
     */
    protected function extractFile($absolutePath, $fileMode, $data)
    {
        if (file_exists($absolutePath)) {
            $this->getLogger()->debug('Skipping file (graceful is enabled, will not override).');

            return;
        }

        parent::extractFile($absolutePath, $fileMode, $data);
    }

}