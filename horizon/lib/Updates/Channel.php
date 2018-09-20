<?php

namespace Horizon\Updates;

class Channel
{

    /**
     * @var Repository
     */
    protected $repo;

    /**
     * @var Version[]
     */
    protected $versions = array();

    /**
     * Constructs a new Channel instance.
     *
     * @param Repository $repo
     * @param object $config
     */
    public function __construct(Repository $repo, $config)
    {
        $this->repo = $repo;

        if (!isset($config->versions)) {
            $config->versions = array();
        }

        foreach ($config->versions as $versionData) {
            $version = new Version($this, $versionData);

            if ($version->isNewerThan($repo->getVersion())) {
                $this->versions[$version->getId()] = $version;
            }
        }
    }

    /**
     * Gets an array of newer versions from this channel.
     *
     * @return Version[]
     */
    public function getNewerVersions()
    {
        return $this->versions;
    }

    /**
     * Gets the repository for this channel.
     *
     * @return Repository
     */
    public function getRepo()
    {
        return $this->repo;
    }

    /**
     * Gets a unique ID for this channel.
     *
     * @return string
     */
    public function getId()
    {
        return sha1(
            $this->repo->getId() .
            $this->repo->getChannel()
        );
    }

}