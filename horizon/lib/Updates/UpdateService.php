<?php

namespace Horizon\Updates;

use Horizon\Framework\Application;
use Horizon\Framework\Core;
use Horizon\Framework\Kernel;
use Horizon\Support\Path;
use Horizon\Logging\Logger;

class UpdateService
{

    /**
     * @var Repository[]
     */
    protected static $repositories;

    /**
     * @var UpdateCollection
     */
    protected static $collection;

    /**
     * @var Logger
     */
    protected static $logger;

    /**
     * Returns a collection of available updates from all repositories.
     *
     * @return UpdateCollection
     */
    public static function getLatestUpdates()
    {
        if (is_null(static::$collection)) {
            @set_time_limit(config('updates.env.max_execution_time'));

            $updates = array();

            foreach (static::getRepositories() as $repo) {
                $channel = $repo->retrieve();
                $versions = $channel->getNewerVersions();

                foreach ($versions as $version) {
                    $updates[] = $version;
                }
            }

            static::$collection = new UpdateCollection($updates);
        }

        return static::$collection;
    }

    /**
     * Gets the absolute path to the certificate bundle or null if it is not configured.
     *
     * @return string|null
     */
    public static function getCertificateBundle()
    {
        $bundle = config('updates.ssl.certificate_authority');

        if (is_string($bundle)) {
            $bundle = Path::resolve(Core::path(), $bundle);
        }

        return $bundle;
    }

    /**
     * Loads and returns update repositories from the service providers.
     *
     * @return Repository[]
     */
    public static function getRepositories()
    {
        if (is_null(static::$repositories)) {
            static::$repositories = Application::collect('Horizon\Updates\Repository');
        }

        return static::$repositories;
    }

    /**
     * Gets the logger instance for the update service.
     *
     * @return Logger
     */
    public static function getLogger()
    {
        if (is_null(static::$logger)) {
            static::$logger = new Logger('updates');
        }

        return static::$logger;
    }

    /**
     * Gets the log output for the update service.
     *
     * @return string
     */
    public static function getLog()
    {
        return (string) static::getLogger();
    }

}
