<?php

namespace Horizon\Provider\Services;

use Horizon;
use Horizon\Utils\Path;
use Horizon\Provider\ServiceProvider;
use Horizon\Extend\Extension;

class ExtensionProvider extends ServiceProvider
{

    /**
     * @var \Exception[]
     */
    protected $failed;

    /**
     * @var Extension[]
     */
    protected $extensions;

    /**
     * Loads extensions from the app/extensions directory.
     */
    public function __invoke()
    {
        $extensionsDir = Path::join(Horizon::APP_DIR, 'extensions');

        $this->extensions = array();
        $this->failed = array();

        if (!file_exists($extensionsDir)) return;
        if (!is_null($this->extensions)) return;

        $dir = new \DirectoryIterator($extensionsDir);

        foreach ($dir as $node) {
            if ($node->isDir() && substr($node->getFilename(), 0, 1) != '.') {
                try {
                    $extension = new Extension($node->getPathname());
                    $this->extensions[] = $extension;
                }
                catch (\Exception $e) {
                    $this->failed[$node->getPathname()] = $e;
                }
            }
        }
    }

    /**
     * Gets an array of loaded extensions.
     *
     * @return Extension[]
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Gets an array of extension paths and their exceptions.
     *
     * @return \Exception[]
     */
    public function getFailedExtensions()
    {
        return $this->failed;
    }

}
