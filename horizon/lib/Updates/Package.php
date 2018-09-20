<?php

namespace Horizon\Updates;

use Horizon\Utils\ZipArchive;
use Horizon\Updates\UpdateException;
use Horizon\Utils\Str;
use Horizon\Encryption\FastEncrypt;

class Package
{

    /**
     * @var Version
     */
    protected $version;

    /**
     * @var ZipArchive
     */
    protected $payload;

    /**
     * @var Script[]
     */
    protected $scripts = array();

    /**
     * Constructs a new Package instance.
     *
     * @param Version $version
     * @param array $files
     */
    public function __construct(Version $version, array $files)
    {
        $logger = UpdateService::getLogger();
        $logger->info('Decompressing payload archive...');

        $this->version = $version;

        // Add the payload
        $this->payload = ZipArchive::fromString($files['payload']);
        unset($files['payload']);

        // Handle archive error
        if ($this->payload->hasError()) {
            $logger->error('Decompression failed, due to an error:', $this->payload->getError());
            $logger->error('The error occurred at innode offset', count($this->payload->getFiles()));

            throw new UpdateException('Error parsing update payload: ' . $this->payload->getError());
        }

        $logger->info('Decompressed', count($this->payload->getFiles()), 'files.');

        // Add the scripts
        foreach ($files as $purpose => $data) {
            $script = new Script($this, $data);
            $this->scripts[$purpose] = $script;
        }
    }

    /**
     * Gets the scripts inside this package.
     *
     * @return Script[]
     */
    public function getScripts()
    {
        return $this->scripts;
    }

    /**
     * Gets the specified script by its name ('upgrade' or 'downgrade') or null if the script doesn't exist.
     *
     * @return Script|null
     */
    public function getScript($name)
    {
        return (isset($this->scripts[$name])) ?
            $this->scripts[$name] :
            null;
    }

    /**
     * Gets the specified archive for staging.
     *
     * @return ZipArchive
     */
    public function getArchive($type)
    {
        return $this->payload;
    }

    /**
     * Gets the version that this package is associated to.
     *
     * @return Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Gets the channel that this package is associated to.
     *
     * @return Channel
     */
    public function getChannel()
    {
        return $this->version->getChannel();
    }

    /**
     * Gets the repository that this package is associated to.
     *
     * @return Repository
     */
    public function getRepo()
    {
        return $this->version->getRepo();
    }

    /**
     * Creates a compressed archive containing all files which this update will overwrite or delete. This does not
     * write the archive to a file or save it in any way.
     *
     * @param bool $encrypt
     * @return ZipArchive
     */
    public function createBackup($encrypt = true)
    {
        $logger = UpdateService::getLogger();
        $script = $this->getScript('upgrade');
        $affected = $script->getFilesToBackUp();

        $logger->info('This update will modify or delete', count($affected), 'files.');
        $logger->info('Creating a compressed backup of those files...');

        $backup = new ZipArchive();
        $backup->markEncrypted();

        if ($backup->isDecryptable()) {
            $logger->info('Encryption is confirmed, backed up files will be ciphered.');
            $logger->warn('This backup will be encrypted with Horizon FastEncrypt.');
            $logger->warn('The key for this encryption relies heavily on your filesystem and is not very secure.');
        }
        else {
            $logger->error('Cannot proceed with backup because the encryption module is faulty.');
            $logger->error('Encryption was enabled on the compressed archive, yet a test decryption failed.');

            throw new UpdateException('Cannot create backup: encryption failed');
        }

        foreach ($affected as $relativePath) {
            $absolutePath = $this->getRepo()->toAbsolutePath($relativePath);
            $horizonPath = ltrim(str_replace('\\', '/', Str::stripBeginning($absolutePath, \Horizon::ROOT_DIR)), '/');

            if (file_exists($absolutePath)) {
                $cipher = FastEncrypt::encrypt(file_get_contents($absolutePath, $horizonPath));
                $backup->createFile($cipher, $horizonPath);

                $logger->info('Backed up file:', $horizonPath, '(cipher size: ' . strlen($cipher) . ')');
            }
        }

        $logger->info('Backup complete.');

        return $backup;
    }

    /**
     * Installs the update package onto the server. Be careful! This will throw an CommandException if there is a failure
     * along the way, and can modify the database, overwrite or delete files, or run code depending on the updates it
     * is installing. Be sure to back things up (ideally automatically) before proceeding.
     *
     * @throws CommandException
     */
    public function install()
    {
        $logger = UpdateService::getLogger();
        $script = $this->getScript('upgrade');

        $logger->info('Starting installation of package', $this->getVersion()->getId(), '(version', $this->getVersion()->getName() . ')');
        $logger->info('Repository:', $this->getRepo()->getId(), $this->getRepo()->getChannel());
        $logger->info('Package:', $this->getVersion()->getUri());

        $logger->notice('This repository is mounted to', $this->getRepo()->getAbsoluteMountPath());
        $logger->notice('Updates will not be able to access files outside of this mount.');

        try {
            $script->execute();
            $logger->info('Installation of package', $this->getVersion()->getId(), 'was successful!');
        }
        catch (CommandException $e) {
            UpdateService::getLogger()->error('Package installation failed due to a CommandException:', $e->getMessage());
            UpdateService::getLogger()->error('File:', $e->getFile(), 'Line:', $e->getLine(), 'Code:', $e->getCode());
            UpdateService::getLogger()->error('Stack:', $e->getTraceAsString());

            throw $e;
        }
        catch (UpdateException $e) {
            UpdateService::getLogger()->error('Package installation failed due to an UpdateException:', $e->getMessage());
            UpdateService::getLogger()->error('File:', $e->getFile(), 'Line:', $e->getLine(), 'Code:', $e->getCode());
            UpdateService::getLogger()->error('Stack:', $e->getTraceAsString());

            throw $e;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

}