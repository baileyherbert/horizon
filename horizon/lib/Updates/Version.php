<?php

namespace Horizon\Updates;

use Horizon\Framework\Core;
use Horizon\Support\Path;
use Horizon\Support\Archive;
use GuzzleHttp\Exception\ServerException;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

class Version
{

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Commit[]
     */
    protected $commits;

    /**
     * @var string[]
     */
    protected $warnings = array();

    /**
     * @var array
     */
    protected $meta = array();

    /**
     * Constructs a new Version instance.
     *
     * @param Channel $channel
     * @param object $config
     */
    public function __construct(Channel $channel, $config)
    {
        $this->channel = $channel;
        $this->version = $config->version;
        $this->type = $config->type;
        $this->warnings = (isset($config->warnings)) ? $config->warnings : array();
        $this->meta = (isset($config->meta)) ? $config->meta : array();
        $this->commits = array();

        if (isset($config->commits)) {
            foreach ($config->commits as $section => $commits) {
                if (is_string($commits)) {
                    $commit = new Commit($this, (object) array(
                        'title' => $commits
                    ));

                    $this->commits[$commit->getId()] = $commit;
                    continue;
                }

                foreach ($commits as $commitDetails) {
                    $commit = new Commit($this, $commitDetails, $section);

                    $this->commits[$commit->getId()] = $commit;
                }
            }
        }
    }

    /**
     * Gets a unique ID for the version.
     *
     * @return string
     */
    public function getId()
    {
        return sha1(
            $this->channel->getId() .
            $this->version .
            $this->type
        );
    }

    /**
     * Gets the channel instance for this version.
     *
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Gets the repository instance for this version.
     *
     * @return Repository
     */
    public function getRepo()
    {
        return $this->channel->getRepo();
    }

    /**
     * Gets the string representation of this version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Alias of getVersion().
     *
     * @return string
     */
    public function getName()
    {
        return $this->version;
    }

    /**
     * Gets an array of commit groups. Each commit group is keyed under the group name (e.g. "New features").
     *
     * @return Commit[][]
     */
    public function getCommits()
    {
        $sections = array();

        foreach ($this->commits as $commit) {
            $section = $commit->getSectionName();

            if (!isset($sections[$section])) {
                $sections[$section] = array();
            }

            $sections[$section][] = $commit;
        }

        return $sections;
    }

    /**
     * Checks if this version is newer than the specified version.
     *
     * @param string $version
     * @return bool
     */
    public function isNewerThan($version)
    {
        return version_compare($this->version, $version) > 0;
    }

    /**
     * Gets the type of update (for example, patch, new feature, or bug fix).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets an array of warnings associated with this update.
     *
     * @return string[]
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Gets an array of metadata provided by the update server.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->meta;
    }

    /**
     * Gets the absolute URI for the update.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->getChannel()->getRepo()->getUri() .
            $this->getChannel()->getRepo()->getChannel() . '/' .
            $this->getVersion();
    }

    /**
     * Checks whether or not the update has already been downloaded to the filesystem.
     *
     * @return bool
     */
    public function isDownloaded()
    {
        return file_exists($this->getDownloadPath());
    }

    /**
     * Gets the absolute path to the file where this update will be downloaded.
     *
     * @return string
     */
    public function getDownloadPath()
    {
        $storagePath = Path::resolve(Core::path(), config('updates.storage'));
        $downloadPath = Path::join($storagePath, $this->getId() . '.hzpack');

        return $downloadPath;
    }

    /**
     * Downloads the update package.
     *
     * @return Package
     */
    public function download()
    {
        $logger = UpdateService::getLogger();

        // Get download path
        $downloadPath = $this->getDownloadPath();

        // Master array to store the downloaded components
        $master = array();

        // Files to download
        $targets = array('upgrade', 'payload');

        $logger->info('Starting download of version', $this->getName());

        // Retrieve files
        foreach ($targets as $target) {
            try {
                $logger->info('Downloading', $target, 'from host', $this->getUri());

                $file = $this->downloadFile($target);
                $master[$target] = $file;

                $logger->info('Success.');
            }
            catch (ClientException $e) {
                $code = $e->getResponse()->getStatusCode();
                $error = 'Unknown status code (' . $code . ')';

                $logger->error('Received an unexpected status code from HTTP host:', $code);

                switch ($code)  {
                    case 403:
                        $error = 'Access denied';
                        break;
                    case 404:
                        $error = 'Not found';
                        break;
                    case 400:
                        $error = 'Bad request';
                        break;
                }

                if ($error == 'Not found' && $target == 'downgrade') {
                    continue;
                }

                throw new UpdateException($error);
            }
            catch (ServerException $e) {
                $logger->error('Server error encountered:', $e->getMessage());
                $logger->error('Status code:', $e->getResponse()->getStatusCode());

                throw new UpdateException('Could not download update due to a server error');
            }
            catch (RequestException $e) {
                $logger->error('Client error encountered:', $e->getMessage());
                $logger->error($e->getTraceAsString());

                throw new UpdateException('Could not connect to server');
            }
            catch (Exception $e) {
                $logger->error('Unknown exception encountered:', $e->getMessage());
                $logger->error($e->getTraceAsString());

                throw new UpdateException('Unhandled exception: ' . $e->getMessage());
            }
        }

        if (!isset($master['payload'])) {
            throw new UpdateException('Missing required package file (payload)');
        }

        if (!isset($master['upgrade'])) {
            throw new UpdateException('Missing required package script (upgrade)');
        }

        return new Package($this, $master);
    }

    /**
     * Downloads the specified file from the update server, for this version, and returns the string data.
     *
     * @param string $name
     * @throws RequestException
     * @return string
     */
    protected function downloadFile($name)
    {
        $uri = $this->getUri() . '/' . $name;
        $client = new Client();

        $timeLimit = ($name == 'payload') ? config('updates.timeout.payload') : config('updates.timeout.script');

        $response = $client->get($uri, array(
            'headers' => array(
                'User-Agent' => sprintf('Horizon Framework (%s) / %s', Core::version(), $this->getId()),
                'Authorization' => $this->getRepo()->getAuthorization()
            ),
            'verify' => (config('updates.peer_validation') ? UpdateService::getCertificateBundle() : false),
            'timeout' => ($timeLimit ?: config('updates.timeout.default')),
            'connect_timeout' => (config('updates.timeout.init') ?: config('updates.timeout.default')),
            'exceptions' => true
        ));

        return $response->getBody()->__toString();
    }

    /**
     * Serializes the update into an array.
     *
     * @return array
     */
    public function toArray()
    {
        $commits = array();

        foreach ($this->commits as $commit) {
            $commits[] = array(
                'id' => $commit->getId(),
                'remoteId' => $commit->getRemoteId(),
                'section' => $commit->getSectionName(),
                'title' => $commit->getTitle()
            );
        }

        return array(
            'repo' => array(
                'id' => $this->getChannel()->getRepo()->getId(),
                'uri' => $this->getChannel()->getRepo()->getUri()
            ),
            'channel' => array(
                'id' => $this->getChannel()->getId(),
                'name' => $this->getChannel()->getRepo()->getChannel()
            ),
            'id' => $this->getId(),
            'version' => $this->getVersion(),
            'uri' => $this->getUri(),
            'type' => $this->getType(),
            'commits' => $commits,
            'meta' => $this->getMetadata(),
            'warnings' => $this->getWarnings()
        );
    }

    /**
     * Serializes the update into JSON.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

}
