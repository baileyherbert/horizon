<?php

namespace Horizon\Updates\Commands;

use Horizon\Updates\Script;
use Horizon\Updates\Command;
use Horizon\Updates\CommandException;
use Horizon\Support\Str;

class ExecuteFile extends Command
{

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var \Exception|null
     */
    protected $error;

    /**
     * @var mixed
     */
    protected $returned;

    /**
     * Parses the arguments.
     *
     * @param array $args
     */
    protected function parse(array $args)
    {
        if (count($args) !== 1) {
            throw new CommandException(sprintf('Expected %d argument, got %d in ExecuteFile()', 1, count($args)));
        }

        $this->fileName = trim($args[0], '/');

        $target = $this->getScript()->getLastCommandByType($this, 'TargetDirectory');
        $staged = $this->getStagedFiles();

        if (!is_null($target)) {
            $this->scope = ltrim($target->getTarget() . '/', '/');
        }

        if (!Str::endsWith($this->fileName, '.php')) {
            throw new CommandException(sprintf('Cannot execute payload file at %s because it is not PHP', $this->fileName));
        }

        foreach ($staged as $file) {
            if ($file['path'] == $this->fileName) {
                $this->data = $file['data'];
                return;
            }
        }

        throw new CommandException(sprintf('Could not find a payload file at %s for execution', $this->fileName));
    }

    /**
     * Executes the command.
     */
    public function execute()
    {
        $fileName = 'tmp_exec.' . basename($this->fileName);
        $targetPath = $this->getRepo()->toAbsolutePath($fileName);
        $oldData = null;

        $this->getLogger()->info('Executing payload file:', $this->fileName);
        $this->getLogger()->info('Extracting temporarily to:', $targetPath);

        if (file_exists($targetPath)) {
            $this->getLogger()->info('The target path already exists, so we will overwrite it and then restore it later.');

            $oldData = file_get_contents($targetPath);
        }

        $written = @file_put_contents($targetPath, $this->data);

        if ($written === false) {
            $this->getLogger()->error('Could not write to file:', $targetPath);
            throw new CommandException('Could not execute payload file: ' . $this->fileName);
        }

        $old = @set_error_handler(function($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $this->getLogger()->info('Executing the script in the current process...');
            $this->getLogger()->info('An error handler has been set to attempt to catch any errors, but if the script stops working here, it failed.');

            $this->returned = require $targetPath;

            $this->getLogger()->info('Script executed successfully.');
        }
        catch (\ErrorException $e) {
            $this->getLogger()->error('The script encountered a PHP error:', $e->getMessage(), 'on line', $e->getLine(), 'severity', $e->getSeverity());
            $this->error = $e;
        }
        catch (Exception $e) {
            $this->getLogger()->error('The script threw an exception:', $e->getMessage(), 'on line', $e->getLine(), 'severity', $e->getSeverity());
            $this->error = $e;
        }

        $originalHandler = null;

        if (version_compare(phpversion(), '5.5.0', '<')) {
            $originalHandler = @set_error_handler(function() { }, 0);
        }
        else {
            $originalHandler = @set_error_handler(null);
        }

        if (is_null($oldData) && file_exists($targetPath)) {
            $this->getLogger()->info('Removing execution file at:', $targetPath);
            $success = @unlink($targetPath);

            if ($success === false) {
                $this->getLogger()->error('Could not unlink file:', $targetPath);
                $this->getLogger()->error('Running the update again should get around this issue.');

                throw new CommandException('Failed to clean up executed file');
            }
        }

        if (!is_null($oldData)) {
            @file_put_contents($targetPath, $oldData);
        }

        if (!is_null($originalHandler)) {
            @set_error_handler($originalHandler);
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
     * Returns an exception or null if there was no error.
     *
     * @return Exception|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the value which was returned by the executed file, or null if nothing was returned or if an error occurred.
     *
     * @return mixed
     */
    public function getReturned()
    {
        return $this->returned;
    }

}
