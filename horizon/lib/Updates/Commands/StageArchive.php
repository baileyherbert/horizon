<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Utils\ZipArchive;

class StageArchive extends Command
{

    /**
     * @var string
     */
    protected $archiveType;

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) !== 1) {
            throw new CommandException(sprintf('Expected %d arguments, got %d in StageArchive()', 1, count($args)));
        }

        $this->archiveType = $args[0];
    }

    /**
     * Validates the data parsed from arguments.
     */
    protected function validate()
    {
        if ($this->archiveType != 'payload' && $this->archiveType != 'restore') {
            throw new CommandException(sprintf('Expected "payload" or "restore", got "%s" in StageArchive()', $this->archiveType));
        }
    }

    /**
     * Gets the archive type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->archiveType;
    }

    /**
     * Gets the ZipArchive instance for the archive.
     *
     * @return ZipArchive
     */
    public function getZipArchive()
    {
        return $this->getScript()->getPackage()->getArchive($this->archiveType);
    }

    /**
     * Executes the command.
     */
    public function execute()
    {
        $this->getLogger()->info('Staged archive:', $this->getType());
    }

}