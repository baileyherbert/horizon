<?php

namespace Horizon\Provider;

use Horizon;
use Horizon\Utils\Path;

trait ServiceKernel
{

    /**
     * @var ServiceProvider[]
     */
    private static $providers = array();

    /**
     * Retrieves and initializes the service providers.
     */
    protected static function initProviders()
    {
        static::$providers = static::fetchProvidersByConfig();
    }

    /**
     * Gets an array of providers. If $type is specified, it returns providers for that service type. Otherwise,
     * an array of arrays for each service type.
     *
     * @param string $type
     * @return ServiceProvider[]|array[]
     */
    public static function getProviders($type = null) {
        $type = trim(strtolower($type));
        $providers = array();

        if (is_null($type)) {
            return static::$providers;
        }


        // Add application-level providers
        if (isset(static::$providers[$type])) {
            foreach (static::$providers[$type] as $o) {
                $providers[] = $o;
            }
        }

        // Add extension-level providers
        foreach (static::getExtensions() as $ext) {
            foreach ($ext->getProviders($type) as $o) {
                $providers[] = $o;
            }
        }

        return $providers;
    }

    /**
     * Fetches providers from the configuration file.
     *
     * @return array[]
     */
    private static function &fetchProvidersByConfig()
    {
        $configFile = Path::join(Horizon::APP_CONFIG_DIR, 'providers.php');
        $providers = array();

        if (file_exists($configFile)) {
            $config = require($configFile);

            foreach ($config as $type => $classNames) {
                $providers[$type] = array();

                foreach ($classNames as $className) {
                    if (class_exists($className, true)) {
                        $providers[$type][] = new $className;
                    }
                }
            }

            return $providers;
        }

        return array();
    }

}
