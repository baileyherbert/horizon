<?php

namespace Horizon\Extension;

use Horizon\Framework\Application;
use Horizon\Framework\Services\Autoloader;
use Horizon\Support\Path;
use Horizon\Support\Services\ServiceProvider;

/**
 * Kernel for extensions.
 */
class Kernel
{

    /**
     * @var Extension[]
     */
    private $extensions = array();

    /**
     * Boots the extension kernel.
     */
    public function boot()
    {
        $this->resolve();
    }

    /**
     * Autoloads extensions through their namespace mapping and vendor directories.
     */
    public function autoload()
    {
        foreach ($this->extensions as $extension) {
            if ($extension->hasNamespace()) {
                $namespace = rtrim($extension->getNamespace(), '\\') . '\\';
                $source = $extension->getSourceDirectory();

                Autoloader::mount($namespace, $source);
            }

            if ($extension->hasAutoLoader()) {
                $path = Path::join($extension->getComposerVendorPath(), 'autoload.php');

                Autoloader::vendor($path);
            }
        }
    }

    /**
     * Loads providers from extensions and registers them in the application.
     */
    public function provide()
    {
        foreach ($this->extensions as $extension) {
            $providers = $extension->getProviders();

            foreach ($providers as $provider) {
                if ($provider instanceof ServiceProvider) {
                    Application::register($provider);
                }
            }
        }
    }

    /**
     * Gets all loaded extensions.
     *
     * @return Extension[]
     */
    public function get()
    {
        return $this->extensions;
    }

    /**
     * Resolves extensions from service providers and stores them internally.
     */
    private function resolve()
    {
        foreach (Application::resolve('Horizon\Extension\Extension') as $extension) {
            $this->extensions[] = $extension;
        }
    }

}
