<?php

namespace Horizon\Extension;

use Horizon\Support\Path;
use Horizon\Support\Services\ServiceProvider;

class Extension
{

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $directoryName;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $publicMapRoute = '/extensions/%s/';

    /**
     * @var string
     */
    protected $publicMapLegacy = '/app/extensions/%s/';

    /**
     * Constructs a new Extension instance.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->directoryName = basename($path);
        $this->configPath = Path::join($path, 'extension.json');

        if (!file_exists($this->configPath)) {
            throw new Exception('Error loading extension: extension.json is missing');
        }

        $this->config = @json_decode(file_get_contents($this->configPath), true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new Exception('Error loading extension: extension.json is not valid JSON');
        }

        if (!isset($this->config['name'])) {
            throw new Exception('extension.json: field "name" is required');
        }

        if (!isset($this->config['version'])) {
            throw new Exception('extension.json: field "version" is required');
        }

        $this->loadDefaults($this->config);
    }

    public function getName()
    {
        return $this->config['name'];
    }

    public function getVersion()
    {
        return $this->config['version'];
    }

    public function getPath()
    {
        return $this->path;
    }

    public function hasSourceDirectory()
    {
        return null != $this->config['source']['path'];
    }

    /**
     * Gets the absolute path to the extension's source directory.
     *
     * @return string
     */
    public function getSourceDirectory() {
        return Path::resolve($this->path, $this->config['source']['path']);
    }

    /**
     * Returns whether or not the extension has a namespace configured.
     *
     * @return bool
     */
    public function hasNamespace()
    {
        return null != $this->config['source']['namespace'];
    }

    /**
     * Gets the namespace for the extension, or returns null if not set.
     *
     * @return string|null
     */
    public function getNamespace() {
        return $this->config['source']['namespace'];
    }

    /**
     * Returns whether or not the extension has composer packages inside it.
     *
     * @return bool
     */
    public function hasComposer()
    {
        return $this->config['composer']['json'] && $this->config['composer']['vendor'];
    }

    /**
     * Gets the absolute path to the composer.json file for this extension, or null if it doesn't have one.
     *
     * @return string|null
     */
    public function getComposerJsonPath()
    {
        if (is_null($this->config['composer']['json'])) {
            return null;
        }

        return Path::resolve($this->path, $this->config['composer']['json']);
    }

    /**
     * Gets the absolute path to the vendor directory for this extension, or null if it doesn't have one.
     *
     * @return string|null
     */
    public function getComposerVendorPath()
    {
        if (is_null($this->config['composer']['vendor'])) {
            return null;
        }

        return Path::resolve($this->path, $this->config['composer']['vendor']);
    }

    /**
     * Returns whether or not the extension has its own composer autoloader.
     *
     * @return bool
     */
    public function hasAutoLoader()
    {
        return $this->hasComposer()
            && $this->config['composer']['autoload'];
    }

    /**
     * Gets the extension's providers matching the specified type. Returns an array of provider instances.
     *
     * @return ServiceProvider[]
     */
    public function getProviders()
    {
        if (!is_array($this->config['providers'])) {
            return array();
        }

        $providers = $this->config['providers'];
        $instances = array();

        foreach ($providers as $className) {
            $instances[] = new $className();
        }

        return $instances;
    }

    /**
     * Loads default values for the extension to ensure it has all options.
     *
     * @param array $config
     * @param array|null $defaults
     */
    private function loadDefaults(&$config, $defaults = null)
    {
        if (is_null($defaults)) {
            $defaults = static::getDefaults();
        }

        foreach ($defaults as $i => $value) {
            if (!isset($config[$i])) {
                $config[$i] = $value;
            }

            if (is_array($value)) {
                $this->loadDefaults($config[$i], $value);
            }
        }
    }

    /**
     * Gets an array of default options for extensions.
     *
     * @return array
     */
    public static function getDefaults()
    {
        static $defaults = array(
            'source' => array(
                'path' => null,
                'namespace' => null
            ),
            'composer' => array(
                'autoload' => false,
                'json' => null,
                'vendor' => null
            ),
            'providers' => array(
                'views' => array(),
                'routes' => array(),
                'updates' => array(),
                'translations' => array()
            )
        );

        return $defaults;
    }

    /**
     * Gets a path to an extension's asset when rewrite routing is enabled.
     *
     * @param string $assetName The full name of the asset (e.g. 'styles/name.css').
     * @return string
     */
    public function getMappedPublicRoute($assetName)
    {
        $asset = ltrim($assetName, '/');
        $base = trim(sprintf($this->publicMapRoute, $this->directoryName), '/');

        return '/' . $base . '/' . $asset;
    }

    /**
     * Gets a path to an extension's asset when rewrite routing is disabled.
     *
     * @param string $assetName The full name of the asset (e.g. 'styles/name.css').
     * @return string
     */
    public function getMappedLegacyRoute($assetName)
    {
        $asset = ltrim($assetName, '/');
        $base = trim(sprintf($this->publicMapLegacy, $this->directoryName), '/');

        return '/' . $base . '/' . $asset;
    }

}
