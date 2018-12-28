<?php

namespace Horizon\Support\Facades;

/**
 * Facade for access to framework-specific information and methods.
 */
class Framework
{

    /**
     * Gets the decoded composer.json object for the framework.
     *
     * @return object
     */
    public static function composer()
    {
        return \Horizon\Foundation\Framework::composer();
    }

    /**
     * Gets the current version of the framework (format x.x.x).
     *
     * @return string
     */
    public static function version()
    {
        return \Horizon\Foundation\Framework::version();
    }

    /**
     * Gets the current edition of the framework.
     *
     * @return string
     */
    public static function edition()
    {
        return \Horizon\Foundation\Framework::edition();
    }

}
