<?php

namespace Horizon\Extension;

use DirectoryIterator;
use Horizon\Foundation\Application;
use Horizon\Support\Services\ServiceProvider;

/**
 * Provides extensions for the application.
 */
class ExtensionServiceProvider extends ServiceProvider
{

    /**
     * @var Exception[]
     */
    protected $exceptions = array();

    public function register()
    {
        $this->bind('Horizon\Extension\Extension', function() {
            $extensionsDir = Application::path('app/extensions');
            if (!file_exists($extensionsDir)) return null;

            $dir = new DirectoryIterator($extensionsDir);
            $extensions = array();

            foreach ($dir as $node) {
                if ($node->isDir() && substr($node->getFilename(), 0, 1) != '.') {
                    try {
                        $extensions[] = new Extension($node->getPathname());
                    }
                    catch (Exception $e) {
                        $this->exceptions[] = $e;
                    }
                }
            }

            return $extensions;
        });

        $this->bind('Horizon\Extension\Exception', function() {
            return $this->exceptions;
        });
    }

    public function provides()
    {
        return array(
            'Horizon\Extension\Extension',
            'Horizon\Extension\Exception'
        );
    }

}
