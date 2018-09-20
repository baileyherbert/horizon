<?php

namespace Horizon\View;

trait ViewKernel
{

    private static $files = array();

    /**
     * Retrieves the full path to a template file, or null if it doesn't exist.
     *
     * @param string $name
     * @return string|null
     */
    public static function getTemplatePath($name)
    {
        $path = null;

        foreach (static::getProviders('views') as $provider) {
            $templatePath = $provider($name);

            if ($templatePath !== null) {
                $path = $templatePath;
            }
        }

        return $path;
    }

    /**
     * Retrieves the full path to a template file, or null if it doesn't exist.
     *
     * @param string $name
     * @return string|null
     */
    public static function getTranslationFiles()
    {
        $path = null;
        $providers = static::getProviders('views');

        for ($i = count($providers) - 1; $i >= 0; $i--) {
            $provider = $providers[$i];
            $templatePath = $provider($name);

            if ($templatePath !== null) {
                $path = $templatePath;
                break;
            }
        }

        return $path;
    }

}
