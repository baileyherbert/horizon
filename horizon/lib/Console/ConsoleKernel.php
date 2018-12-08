<?php

namespace Horizon\Console;

use Exception;
use Horizon;

use Symfony\Component\Console\Application;

trait ConsoleKernel
{

    /**
     * @var Application
     */
    protected static $consoleApp;

    protected static function bootConsole()
    {
        static::initErrorHandling();
        static::configure();
        static::initAutoloader();
        static::initProviders();
        static::initLanguageBucket();

        static::initConsoleApp();
        static::initCommands();
        static::runConsoleApp();
    }

    private static function initConsoleApp()
    {
        static::$consoleApp = new Application();
        static::$consoleApp->setName(config('console.name', 'Horizon'));
        static::$consoleApp->setVersion(config('console.version', Horizon::VERSION));
    }

    private static function initCommands()
    {
        $commands = config('console.commands', array());

        foreach ($commands as $name) {
            if (class_exists($name)) {
                static::$consoleApp->add(new $name());
            }
        }
    }

    private static function runConsoleApp()
    {
        static::$consoleApp->run();
    }

}
