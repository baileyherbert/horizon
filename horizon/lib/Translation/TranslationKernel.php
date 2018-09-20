<?php

namespace Horizon\Translation;

trait TranslationKernel
{

    /**
     * @var LanguageBucket
     */
    protected static $bucket;

    /**
     * Creates an empty language bucket for storing languages in the kernel.
     */
    protected static function initLanguageBucket()
    {
        static::$bucket = new LanguageBucket();
    }

    /**
     * Gets the global language bucket instance.
     *
     * @return LanguageBucket
     */
    public static function getLanguageBucket()
    {
        return static::$bucket;
    }

    /**
     * Adds a language to the global language bucket. The language will automatically be loaded and used for
     * future translation calls.
     *
     * @param Language $language
     */
    public static function addLanguage(Language $language)
    {
        static::$bucket->add($language);
    }

    /**
     * Loads and parses translation files into the language bucket.
     */
    protected static function loadLanguages()
    {
        foreach (static::getProviders('translations') as $provider) {
            $files = $provider();

            foreach ($files as $filePath) {
                static::addLanguage(new Language($filePath));
            }
        }
    }

}
