<?php

namespace Horizon\Updates;

class Commit
{

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $commitId;

    /**
     * @var string|null
     */
    protected $sectionName;

    /**
     * Constructs a new Commit instance.
     */
    public function __construct(Version $version, $config, $sectionName = null)
    {
        if (is_string($config)) {
            $config = (object) array(
                'title' => $config
            );
        }

        $this->version = $version;
        $this->title = $config->title;
        $this->commitId = (isset($config->commit)) ? $config->commit : null;
        $this->sectionName = $sectionName;
    }

    /**
     * Gets the unique ID for this commit.
     *
     * @return string
     */
    public function getId()
    {
        return sha1(
            $this->version->getId() . '->' .
            $this->title
        );
    }

    /**
     * Gets the version instance for this commit.
     *
     * @return Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Gets the title text for this commit.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets the ID of the remote commit (for example, from source control), or null if not provided.
     *
     * @return string|null
     */
    public function getRemoteId()
    {
        return $this->commitId;
    }

    /**
     * Gets the name of the section for this commit, or null if it wasn't set inside a section.
     *
     * @return string|null
     */
    public function getSectionName()
    {
        return $this->sectionName;
    }

}