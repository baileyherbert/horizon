<?php

namespace Horizon\Foundation;

use Horizon\Exception\Kernel as ExceptionKernel;
use Horizon\Console\Kernel as ConsoleKernel;
use Horizon\Foundation\Services\Autoloader;
use Horizon\Routing\Kernel as RoutingKernel;
use Horizon\Http\Kernel as HttpKernel;
use Horizon\Database\Kernel as DatabaseKernel;
use Horizon\Support\Path;
use Horizon\Support\Profiler;
use Horizon\Support\Services\ServiceProvider;
use Horizon\Translation\Kernel as TranslationKernel;
use Horizon\Extension\Kernel as ExtensionKernel;
use Horizon\View\Kernel as ViewKernel;

/**
 * This class is the framework kernel, which is responsible for booting and initializing the framework as well as the
 * application's controllers and services.
 *
 * @internal
 */
class Kernel
{

    private $booted = false;

    private $http;
    private $database;
    private $translation;
    private $view;
    private $console;
    private $extension;
    private $exception;
    private $routing;

    /**
     * Starts the framework.
     */
    public function boot()
    {
        // Do nothing if we've already booted
        if ($this->booted) return;

        // Start profiling
        Profiler::start('kernel');

        // Start the error handler
        $this->exception()->boot();

        // Set basic ini settings
        $this->setRuntimeConfiguration();

        // Autoload the application and framework
        $this->autoload();

        // Start custom error reporters
        $this->exception()->init();

        // Run boot scripts where priority=0
        $this->invokeBootScripts(0);

        // Load service providers
        $this->loadProviders();

        // Load extensions
        $this->extension()->boot();
        $this->extension()->autoload();
        $this->extension()->provide();

        // Boot the application
        Application::boot();

        // Load routes
        $this->routing()->boot();

        // Load translations
        $this->translation()->boot();

        // Run boot scripts where priority=1
        $this->invokeBootScripts(1);

        // Boot into console mode when in the console environment
        if (Application::environment() == 'console') {
            $this->console()->boot();
            return;
        }

        // Start the http kernel
        $this->http()->boot();

        // Run boot scripts where priority=2
        $this->invokeBootScripts(2);

        // Prepare the view kernel
        $this->view()->boot();

        // Run the controller
        $this->http()->execute(function() {
            $this->invokeBootScripts(3);
        });

        // Save state
        $this->booted = true;
    }

    /**
     * Shuts down the framework.
     */
    public function shutdown()
    {
        $this->database()->close();
        die;
    }

    /**
     * Sets basic PHP ini and other settings to match the app configuration.
     */
    private function setRuntimeConfiguration()
    {
        date_default_timezone_set(config('app.timezone'));
    }

    /**
     * Starts the autoloader and mounts core namespaces.
     */
    private function autoload()
    {
        // Get namespaces from configuration
        foreach (config('namespaces.map') as $namespace => $relativePath) {
            Autoloader::mount($namespace, Application::path($relativePath));
        }

        // Autoload composer
        Autoloader::vendor(Application::path('app/vendor/autoload.php'));
    }

    /**
     * Runs boot scripts with the specified priority (0 to 2).
     *
     * @param int $priority
     */
    private function invokeBootScripts($priority)
    {
        $classes = config('app.bootstrap', config('app.boot', array()));

        foreach ($classes as $action => $p) {
            if ($p == $priority) {
                if (is_string($action)) {
                    if (strpos($action, '::') !== false) {
                        list($className, $methodName) = explode('::', $action, 2);

                        $class = new \ReflectionClass($className);
                        $method = $class->getMethod($methodName);

                        if ($method->isStatic()) {
                            forward_static_call(array($className, $methodName));
                            continue;
                        }

                        $action = array(new $className(), $methodName);
                    }
                    else {
                        $action = array(new $action(), '__invoke');
                    }
                }

                call_user_func_array($action, array());
            }
        }
    }

    /**
     * Loads service providers and registers them in the application.
     *
     * @throws \Horizon\Exception\HorizonException
     */
    private function loadProviders()
    {
        $providers = Application::config('providers', array());

        foreach ($providers as $className) {
            if (class_exists($className)) {
                $provider = new $className();

                if ($provider instanceof ServiceProvider) {
                    Application::register($provider);
                }
            }
        }
    }

    /* @return HttpKernel */
    public function http() { return $this->http ?: ($this->http = new HttpKernel()); }

    /* @return DatabaseKernel */
    public function database() { return $this->database ?: ($this->database = new DatabaseKernel()); }

    /* @return TranslationKernel */
    public function translation() { return $this->translation ?: ($this->translation = new TranslationKernel()); }

    /* @return ViewKernel */
    public function view() { return $this->view ?: ($this->view = new ViewKernel()); }

    /* @return ConsoleKernel */
    public function console() { return $this->console ?: ($this->console = new ConsoleKernel()); }

    /* @return ExtensionKernel */
    public function extension() { return $this->extension ?: ($this->extension = new ExtensionKernel()); }

    /* @return ExceptionKernel */
    public function exception() { return $this->exception ?: ($this->exception = new ExceptionKernel()); }

    /* @return RoutingKernel */
    public function routing() { return $this->routing ?: ($this->routing = new RoutingKernel()); }

}
