<?php

namespace Horizon\Provider\Services;

use Horizon;
use Horizon\Support\Str;
use Horizon\Support\Path;
use Horizon\Provider\ServiceProvider;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class TranslationProvider extends ServiceProvider
{

    /**
     * Returns an array of absolute paths to translation files for loading.
     *
     * @return string[]
     */
    public function __invoke()
    {
        $translationsPath = Path::join(Horizon::APP_DIR, 'translations');

        if (!file_exists($translationsPath)) {
            return array();
        }

        return $this->getPaths($translationsPath);
    }

    /**
     * Gets an array of SIT files in the specified directory.
     *
     * @param string $root Absolute path to root directory.
     * @return string[]
     */
    private function getPaths($root)
    {
        $dirIterator = new RecursiveDirectoryIterator($root);
        $fileIterator = new RecursiveIteratorIterator($dirIterator);

        $files = array();

        foreach ($fileIterator as $file) {
            if ($file->isDir()) continue;
            if (!Str::endsWith($file->getFilename(), '.sit')) continue;

            $files[] = $file->getPathname();
        }

        return $files;
    }

}
