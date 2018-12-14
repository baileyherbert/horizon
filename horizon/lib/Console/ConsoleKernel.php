<?php

namespace Horizon\Console;

use Exception;
use Horizon;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ConsoleKernel
{

    /**
     * @var Application
     */
    protected static $consoleApp;

    /**
     * @var InputInterface
     */
    protected static $input;

    /**
     * @var OutputInterface
     */
    protected static $output;

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

    public static function __callStatic($name, $arguments)
    {
        if ($name == '__initCommand') {
            static::$input = $arguments[0];
            static::$output = $arguments[1];
        }
    }

    private static function runConsoleApp()
    {
        static::$consoleApp->run();
    }

    /**
     * @return InputInterface
     */
    public static function getConsoleInput()
    {
        return static::$input;
    }

    /**
     * @return OutputInterface
     */
    public static function getConsoleOutput()
    {
        return static::$output;
    }

}
