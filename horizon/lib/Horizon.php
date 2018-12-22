<?php

$composer = json_decode(file_get_contents(dirname(__DIR__) . '/composer.json'));

define('FRAMEWORK_HORIZON_VERSION', $composer->version);
define('FRAMEWORK_HORIZON_EDITION', substr($composer->name, strpos($composer->name, '/') + 1));
define('FRAMEWORK_PHP_VERSION', phpversion());

define('SLASH', DIRECTORY_SEPARATOR);

define('FRAMEWORK_HORIZON_ROOT', dirname(dirname(dirname(__FILE__))));

define('FRAMEWORK_APP_DIR', FRAMEWORK_HORIZON_ROOT . SLASH . 'app');
define('FRAMEWORK_APP_CONFIG_DIR', FRAMEWORK_HORIZON_ROOT . SLASH . 'app' . SLASH . 'config');
define('FRAMEWORK_APP_SRC_DIR', FRAMEWORK_HORIZON_ROOT . SLASH . 'app' . SLASH . 'src');
define('FRAMEWORK_APP_ROUTES_DIR', FRAMEWORK_HORIZON_ROOT . SLASH . 'app' . SLASH . 'routes');

define('FRAMEWORK_HORIZON_DIR', FRAMEWORK_HORIZON_ROOT . SLASH . 'horizon');
define('FRAMEWORK_HORIZON_LIB_DIR', FRAMEWORK_HORIZON_ROOT . SLASH . 'horizon' . SLASH . 'lib');
define('FRAMEWORK_HORIZON_TESTS_DIR', FRAMEWORK_HORIZON_ROOT . SLASH . 'horizon' . SLASH . 'tests');

define('FRAMEWORK_VENDOR_DIR', FRAMEWORK_HORIZON_DIR . SLASH . 'vendor');
define('FRAMEWORK_COMPOSER_FILE', FRAMEWORK_HORIZON_DIR . SLASH . 'composer.json');

if (version_compare(FRAMEWORK_PHP_VERSION, '5.4.0', '<')) {
    http_response_code(500);
    die("Your current PHP version (" . FRAMEWORK_PHP_VERSION . ") is not supported by the Horizon Framework. Please upgrade to at least PHP 5.4 to run this application.");
}

class Horizon
{

    /**
     * Current version of the framework.
     *
     * @var string
     */
    const VERSION = FRAMEWORK_HORIZON_VERSION;

    /**
     * Current edition of the framework.
     *
     * @var string
     */
    const EDITION = FRAMEWORK_HORIZON_EDITION;

    /**
     * Absolute path of the root directory for this application (above the 'horizon' and 'app' directories).
     *
     * @var string
     */
    const ROOT_DIR = FRAMEWORK_HORIZON_ROOT;

    /**
     * Absolute path of the vendor directory for the application.
     *
     * @var string
     */
    const VENDOR_DIR = FRAMEWORK_VENDOR_DIR;

    /**
     * Absolute path of the composer.json file for the application.
     *
     * @var string
     */
    const COMPOSER_FILE = FRAMEWORK_COMPOSER_FILE;

    /**
     * Absolute path to the 'app' directory.
     *
     * @var string
     */
    const APP_DIR = FRAMEWORK_APP_DIR;

    /**
     * Absolute path to the 'app/config' directory.
     *
     * @var string
     */
    const APP_CONFIG_DIR = FRAMEWORK_APP_CONFIG_DIR;

    /**
     * Absolute path to the 'app/src' directory.
     *
     * @var string
     */
    const APP_SRC_DIR = FRAMEWORK_APP_SRC_DIR;

    /**
     * Absolute path to the 'app/routes' directory.
     *
     * @var string
     */
    const APP_ROUTES_DIR = FRAMEWORK_APP_ROUTES_DIR;

    /**
     * Absolute path to the 'horizon' directory.
     *
     * @var string
     */
    const HORIZON_DIR = FRAMEWORK_HORIZON_DIR;

    /**
     * Absolute path to the 'horizon/lib' directory.
     *
     * @var string
     */
    const HORIZON_LIB_DIR = FRAMEWORK_HORIZON_LIB_DIR;

    /**
     * Absolute path to the 'horizon/tests' directory.
     *
     * @var string
     */
    const HORIZON_TESTS_DIR = FRAMEWORK_HORIZON_TESTS_DIR;

    /**
     * Gets the current environment mode. If the first argument contains a string, returns a boolean representing
     * whether the provided string matches the environment or not (case-insensitive).
     *
     * Possible environments are ('production', 'test', 'cli').
     *
     * @param string $matches
     * @return string|bool
     */
    public static function environment($matches = null)
    {
        $value = \Horizon\Framework\Application::environment();

        // Test against $matches argument
        if (!is_null($matches)) {
            return strcasecmp($value, $matches) === 0;
        }

        // Return environment mode
        return $value;
    }

}
