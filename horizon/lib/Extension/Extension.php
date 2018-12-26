<?php

namespace Horizon\Extension;

use Horizon\Support\Path;

/**
 * Base class for an extension loaded from the "/app/extensions/" directory.
 */
class Extension
{

    /**
     * Absolute path to the extension's root directory.
     *
     * @var string
     */
    protected $path;

    /**
     * Absolute path to the extension's configuration file.
     *
     * @var string
     */
    protected $configFilePath;

    /**
     * The extension's main configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * An instance of the extension's main class if applicable.
     *
     * @var object|null
     */
    protected $instance;

    /**
     * Extension constructor.
     *
     * @param string $path
     * @throws Exception
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->configFilePath = Path::join($path, basename($path) . '.php');

        if (!file_exists($this->configFilePath)) {
            throw new Exception($this->path, 'Extension was missing its main configuration file.');
        }

        $this->loadConfiguration();
    }

    /**
     * Returns the absolute path to this extension's root directory.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return array_get($this->config, 'name');
    }

    /**
     * Returns the description of the extension.
     *
     * @return string
     */
    public function getDescription()
    {
        return array_get($this->config, 'description');
    }

    /**
     * Returns the version of the extension.
     *
     * @return string
     */
    public function getVersion()
    {
        return array_get($this->config, 'version');
    }

    /**
     * Returns the name of the main class for this extension, or null if it doesn't have one.
     *
     * @return string|null
     */
    public function getMainClassName()
    {
        return array_get($this->config, 'main');
    }

    /**
     * Returns an array of namespaces to paths that this extension would like mapped. The key is the namespace prefix,
     * and the value is the absolute path to map it to.
     *
     * @return string[]
     */
    public function getNamespaces()
    {
        static $processed;

        if (is_null($processed)) {
            $namespaces = array_get($this->config, 'namespaces');
            $processed = array();

            foreach ($namespaces as $namespace => $relativePath) {
                $relativePath = ltrim($relativePath, '/\\');
                $absolutePath = Path::join($this->getPath(), $relativePath);

                $processed[$namespace] = $absolutePath;
            }
        }

        return $processed;
    }

    /**
     * Returns an array of file paths to require (for autoloading, helpers, or other purposes). The value of each entry
     * is an absolute path to a file, not checked for existence.
     *
     * @return string[]
     */
    public function getFiles()
    {
        static $processed;

        if (is_null($processed)) {
            $files = array_get($this->config, 'files');
            $processed = array();

            foreach ($files as $relativePath) {
                $relativePath = ltrim($relativePath, '/\\');
                $absolutePath = Path::join($this->getPath(), $relativePath);

                $processed[] = $absolutePath;
            }
        }

        return $processed;
    }

    /**
     * Returns an array of provider class names registered to this extension.
     *
     * @return string[]
     */
    public function getProviders()
    {
        return array_get($this->config, 'providers');
    }

    /**
     * Returns true if the extension is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Returns the instance of the extension's main class, if it has one. Otherwise, null.
     *
     * @return object|null
     */
    public function instance()
    {
        if (is_null($this->instance)) {
            $className = $this->getMainClassName();

            if (!is_null($className)) {
                $this->instance = new $className($this);
            }
        }

        return $this->instance;
    }

    /**
     * Loads the configuration file and sets defaults for missing entries.
     *
     * @throws Exception
     */
    protected function loadConfiguration()
    {
        $this->config = require($this->configFilePath);

        if (!is_array($this->config)) throw new Exception($this->path, 'Extension\'s main configuration file did not return an array.');
        if (is_null(array_get($this->config, 'name'))) throw new Exception($this->path, 'Extension name is required.');
        if (is_null(array_get($this->config, 'version'))) throw new Exception($this->path, 'Extension version is required.');

        // Set defaults for incorrect or missing values
        if (!is_string(array_get($this->config, 'description'))) $this->config['description'] = '';
        if (!is_string(array_get($this->config, 'main'))) $this->config['main'] = null;
        if (!is_array(array_get($this->config, 'namespaces'))) $this->config['namespaces'] = array();
        if (!is_array(array_get($this->config, 'files'))) $this->config['files'] = array();
        if (!is_array(array_get($this->config, 'providers'))) $this->config['providers'] = array();
    }

}
