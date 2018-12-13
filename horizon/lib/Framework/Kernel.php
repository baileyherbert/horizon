<?php

namespace Horizon\Framework;

use Horizon;
use Horizon\Routing\RouteLoader;

use Horizon\Database\DatabaseKernel;
use Horizon\Extend\ExtensionKernel;
use Horizon\Provider\ServiceKernel;
use Horizon\Translation\TranslationKernel;
use Horizon\View\ViewKernel;
use Horizon\Http\HttpKernel;
use Horizon\Console\ConsoleKernel;

use Horizon\Utils\TimeProfiler;
use Horizon\Utils\Path;
use Horizon\Exception\ErrorHandler;
use Horizon\Exception\ErrorMiddleware;

class Kernel
{

    use DatabaseKernel;
    use ExtensionKernel;
    use ServiceKernel;
    use TranslationKernel;
    use ViewKernel;
    use HttpKernel;
    use ConsoleKernel;

    /**
     * Starts the framework init process.
     */
    public static function boot()
    {
        TimeProfiler::start('kernel');

        if (defined('CONSOLE_MODE')) {
            return static::bootConsole();
        }

        static::initErrorHandling();
        static::configure();
        static::initAutoloader();
        static::runBootScripts(0);
        static::initProviders();
        static::loadExtensions();
        static::loadExtensionVendors();
        static::initExtensionAutoloaders();
        static::loadRoutes();
        static::initLanguageBucket();
        static::runBootScripts(1);
        static::prepareHttp();
        static::prepareSubdirectory();
        static::makeRequest();
        static::runBootScripts(2);
        static::match();
    }

    /**
     * Initializes the Whoops error handler if the server supports it.
     */
    protected static function initErrorHandling()
    {
        set_error_handler(function($severity, $message, $file, $line) {
            ErrorMiddleware::executeRuntimeError($severity, $message, $file, $line);
        });

        set_exception_handler(function($exception) {
            ErrorMiddleware::executeException($exception);
        });

        register_shutdown_function(function() {
            $error = error_get_last();

            if (!is_null($error)) {
                ErrorMiddleware::executeShutdownError($error['type'], $error['message'], $error['file'], $error['line']);
            }
        });
    }

    /**
     * Configures PHP options based on configuration files.
     */
    protected static function configure()
    {
        ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
        ini_set('display_errors', config('app.display_errors'));
        ini_set('log_errors', config('app.log_errors'));
        ini_set('error_log', Horizon::APP_DIR . SLASH . 'error_log');

        date_default_timezone_set(config('app.timezone'));
    }

    /**
     * Initializes the autoloader for configured namespaces.
     */
    protected static function initAutoloader()
    {
        $mapping = array();

        // Get namespaces from configuration
        foreach (config('namespaces.map') as $namespace => $relativePath) {
            $namespace = trim($namespace, '\\') . '\\';
            $relativePath = Path::join(Horizon::ROOT_DIR, ltrim($relativePath, '/'));

            $mapping[$namespace] = $relativePath;
        }

        // Autoload
        spl_autoload_register(function($className) use ($mapping) {
            $className = ltrim($className, '\\');

            foreach ($mapping as $prefix => $mount) {
                $len = strlen($prefix);

                if (strncmp($prefix, $className, $len) !== 0) {
                    continue;
                }

                $relativeClass = substr($className, $len);
                $file = Path::join($mount, str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php');

                if (file_exists($file)) {
                    require $file;
                }
            }
        });

        // App vendor
        $autoloadPath = Path::join(Horizon::APP_DIR, 'vendor/autoload.php');

        if (file_exists($autoloadPath)) {
            require $autoloadPath;
        }
    }

    /**
     * Initializes the autoloader for extensions.
     */
    protected static function initExtensionAutoloaders()
    {
        $mapping = array();

        // Get extension namespaces
        $extensions = static::getExtensionNamespaces();

        foreach ($extensions as $namespace => $absolutePath) {
            $namespace = trim($namespace, '\\') . '\\';

            if (!isset($mapping[$namespace])) {
                $mapping[$namespace] = $absolutePath;
            }
        }

        // Autoload
        spl_autoload_register(function($className) use ($mapping) {
            $className = ltrim($className, '\\');

            foreach ($mapping as $prefix => $mount) {
                $len = strlen($prefix);

                if (strncmp($prefix, $className, $len) !== 0) {
                    continue;
                }

                $relativeClass = substr($className, $len);
                $file = Path::join($mount, str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php');

                if (file_exists($file)) {
                    require $file;
                }
            }
        });
    }

    /**
     * Executes the route files.
     */
    protected static function loadRoutes()
    {
        foreach (static::getProviders('routes') as $provider) {
            $files = $provider();

            if (is_array($files)) {
                foreach ($files as $file) {
                    RouteLoader::loadRouteFile($file);
                }
            }
        }
    }

    /**
     * Runs boot scripts with the specified priority (0 to 2).
     *
     * @param int $priority
     * @return void
     */
    protected static function runBootScripts($priority)
    {
        $classes = config('app.bootstrap', array());

        foreach ($classes as $action => $p) {
            if ($p == $priority) {
                if (is_string($action)) {
                    if (strpos($action, '::') !== false) {
                        list($className, $methodName) = explode('::', $action, 2);
                        $action = array(new $className(), $methodName);
                    }
                    else {
                        $action = array(new $className(), '__invoke');
                    }
                }

                call_user_func_array($action, array());
            }
        }
    }

    /**
     * Terminates the page.
     */
    public static function close()
    {
        static::closeDatabase();
        die;
    }

}
