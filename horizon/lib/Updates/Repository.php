<?php

namespace Horizon\Updates;

use Horizon\Utils\Path;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use Horizon\Utils\Str;

class Repository
{

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $channel;

    /**
     * @var string
     */
    protected $mount = '/';

    /**
     * @var UpdateChannel
     */
    protected $instance;

    /**
     * @var string
     */
    protected $authorization = '';

    /**
     * @var string
     */
    protected $version;

    /**
     * Constructs a new Repository instance.
     *
     * @param string $uri
     * @param string|null $channel
     * @param string|null $version
     */
    public function __construct($uri, $channel = null, $version = null)
    {
        $this->uri = $uri;
        $this->channel = $channel;
        $this->version = $version;
    }

    /**
     * Sets the path (relative to Horizon's root directory) in which updates from this repository will be installed.
     * This also prevents any updates from affecting files outside of the mounted path, as a security feature.
     *
     * @param string $rootPath
     */
    public function setMountPath($rootPath)
    {
        $this->mount = $rootPath;
    }

    /**
     * Sets the channel from which updates will be received from this repository.
     *
     * @param string $channelName
     */
    public function setChannel($channelName)
    {
        $this->channel = $channelName;
    }

    /**
     * Sets the current version of the component which this repository updates.
     *
     * @param string $version
     */
    public function setCurrentVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Sets the authorization header for requests and downloads.
     *
     * @param string $string
     */
    public function setAuthorization($string)
    {
        $this->authorization = $string;
    }

    /**
     * Gets the authorization header for requests and downloads.
     *
     * @return string
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * Gets the mount path for this repository, which is a path relative to Horizon's root directory that updates can
     * install to and are limited to.
     *
     * @return string
     */
    public function getMountPath()
    {
        return $this->mount;
    }

    /**
     * Gets the mount path for this repository, which is an absolute path in or equal to Horizon's root directory that
     * updates can install to and are limited to.
     *
     * @return string
     */
    public function getAbsoluteMountPath()
    {
        while (Str::startsWith($this->mount, '/')) {
            $this->mount = substr($this->mount, 1);
        }

        return Path::resolve(\Horizon::ROOT_DIR, $this->mount);
    }

    /**
     * Converts the string to a relative path.
     */
    public function toRelativePath($path)
    {
        $mount = $this->getAbsoluteMountPath();

        if (Str::startsWith($path, $mount)) {
            $path = substr($path, strlen($mount));
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        return $path;
    }

    /**
     * Converts the string to an absolute path on the filesystem based on the repository's mounted directory.
     *
     * @param string $path
     * @return string
     */
    public function toAbsolutePath($path)
    {
        $relative = $this->toRelativePath($path);
        $mount = $this->getAbsoluteMountPath();

        return Path::join($mount, $relative);
    }

    /**
     * Checks if the specified absolute file path is within the repository's mounted directory.
     */
    public function isPathMounted($path)
    {
        return Str::startsWith($path, $this->getAbsoluteMountPath());
    }

    /**
     * Gets the name of the channel that this repository is subscribed to.
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Gets the URI of the repository. This will always end with a forward slash.
     *
     * @return string
     */
    public function getUri()
    {
        return rtrim($this->uri, '/') . '/';
    }

    /**
     * Gets the current version of the component which this repository updates.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Gets a unique ID for this repository.
     *
     * @return string
     */
    public function getId()
    {
        return sha1($this->getUri() . $this->getChannel() . $this->getMountPath());
    }

    /**
     * Retrieves the update channel from this repository.
     *
     * @throws UpdateException
     * @return Channel
     */
    public function retrieve()
    {
        if (!is_null($this->instance)) {
            return $this->instance;
        }

        if (is_null($this->getVersion())) {
            throw new UpdateException('Cannot check for updates because current version is not set');
        }

        if (config('updates.ssl.enforce_security_policy')) {
            if (substr($this->getUri(), 0, 6) != 'https:') {
                throw new UpdateException('Security policy violation, cannot enforce SSL validation on repository');
            }
        }

        try {
            $response = $this->exec();
        }
        catch (\Exception $e) {
            throw new UpdateException(sprintf('Unhandled repo exception: %s', $e->getMessage()));
        }

        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();
            $data = @json_decode($body);

            if (json_last_error() == JSON_ERROR_NONE && $data != null) {
                $this->instance = new Channel($this, $data);
            }
            else {
                throw new UpdateException('Failed fetching channel, got invalid response');
            }
        }
        else {
            throw new UpdateException(sprintf('Failed fetching channel, got status code %d', $response->getStatusCode()));
        }

        return $this->instance;
    }

    /**
     * Executes an HTTP request to fetch the channel manifest.
     *
     * @return ResponseInterface
     */
    protected function exec()
    {
        $channelUri = $this->getUri() . $this->getChannel() . '/' . $this->getChannel() . '.json';

        $client = new Client();
        return $client->get($channelUri, array(
            'headers' => array(
                'User-Agent' => sprintf('Horizon Framework (%s)', \Horizon::VERSION),
                'Authorization' => $this->getAuthorization()
            ),
            'verify' => (config('updates.peer_validation') ? UpdateService::getCertificateBundle() : false),
            'timeout' => (config('updates.timeout.channel') ?: config('updates.timeout.default')),
            'connect_timeout' => (config('updates.timeout.init') ?: config('updates.timeout.default')),
            'exceptions' => false
        ));
    }

}