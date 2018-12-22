<?php

namespace Horizon\Extension;

use DirectoryIterator;
use Horizon\Framework\Application;
use Horizon\Support\Services\ServiceProvider;

/**
 * Provides extensions for the application.
 */
class ExtensionServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->bind('Horizon\Extension\Extension', function() {
            $extensionsDir = Application::path('app/extensions');
            if (!file_exists($extensionsDir)) return;

            $dir = new DirectoryIterator($extensionsDir);
            $extensions = array();

            foreach ($dir as $node) {
                if ($node->isDir() && substr($node->getFilename(), 0, 1) != '.') {
                    try {
                        $extensions[] = new Extension($node->getPathname());
                    }
                    catch (\Exception $e) {
                        //$this->failed[$node->getPathname()] = $e;
                    }
                }
            }
        });
    }

    public function provides()
    {
        return array(
            'Horizon\Extension\Extension'
        );
    }

}
