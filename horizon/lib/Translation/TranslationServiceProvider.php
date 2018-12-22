<?php

namespace Horizon\Translation;

use Horizon\Framework\Application;
use Horizon\Support\Services\ServiceProvider;

use Horizon\Support\Str;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Provides translations for views and controllers.
 */
class TranslationServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->bind('Horizon\Translation\Language', function() {
            $translationsPath = Application::path('app/translations');
            if (!file_exists($translationsPath)) return;

            $dirIterator = new RecursiveDirectoryIterator($translationsPath);
            $fileIterator = new RecursiveIteratorIterator($dirIterator);

            $languages = array();

            foreach ($fileIterator as $file) {
                if ($file->isDir()) continue;
                if (!Str::endsWith($file->getFilename(), '.sit')) continue;

                $languages[] = new Language($file->getPathname());
            }

            return $languages;
        });
    }

    public function provides()
    {
        return array(
            'Horizon\Translation\Language'
        );
    }

}
