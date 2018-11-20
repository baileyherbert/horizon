<?php

namespace Horizon\Exception;

use Exception;
use Horizon\Framework\Kernel;

class ErrorMiddleware
{

    /**
     * Handles a runtime error (a standard error that occurs while the page is running).
     *
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public static function executeRuntimeError($severity, $message, $file, $line)
    {
        $error = new HorizonError($message, $severity, $file, $line, 'runtime');
        static::execute($error);
    }

    /**
     * Handles a shutdown error (a fatal error that terminates the page and forces it to shutdown).
     *
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public static function executeShutdownError($severity, $message, $file, $line)
    {
        $error = new HorizonError($message, $severity, $file, $line, 'shutdown');
        static::execute($error);
    }

    /**
     * Handles an uncaught exception.
     *
     * @param Exception|Error $exception
     * @return void
     */
    public static function executeException($exception)
    {
        $reflect = new \ReflectionClass($exception);
        $shortName = $reflect->getShortName();

        $message = sprintf(
            "Uncaught exception '%s' with message '%s' in %s:%d Stack trace: %s",
            $shortName,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        $error = new HorizonError($message, E_ERROR, $exception->getFile(), $exception->getLine(), 'exception', $exception);
        static::execute($error);
    }

    /**
     * Executes the given HorizonError (logs, renders, and reports), and then terminates the page if appropriate based
     * on the severity level.
     *
     * @param HorizonError $error
     * @return void
     */
    public static function execute(HorizonError $error)
    {
        $errorHandler = static::getErrorHandler();

        // Report the error
        forward_static_call(array($errorHandler, 'report'), $error);

        // Log the error
        if (static::canLog($error)) {
            call_user_func(array($errorHandler, 'log'), $error);
        }

        // Render the error
        if (static::canRender($error)) {
            call_user_func(array($errorHandler, 'render'), $error);
        }

        // Terminate the page
        if (static::canTerminate($error)) {
            // If the error wasn't rendered, let's render the 500 error page
            if (!static::canRender($error)) {
                Kernel::showErrorPage(500);
            }

            terminate();
        }
    }

    /**
     * Checks if the error can be logged.
     *
     * @param HorizonError $error
     * @return bool
     */
    private static function canLog(HorizonError $error)
    {
        // Always false when error logging is disabled
        if (!config('errors.log_errors', true)) {
            return false;
        }

        // Handle silence operator (@)
        if (error_reporting() === 0) {
            if ($error->getLevel() < 5) {
                return false;
            }
        }

        // Only log errors that are above or equal to the configured severity
        if ($error->getLevel() < config('errors.log_sensitivity')) {
            return false;
        }

        // Log the error
        return true;
    }

    /**
     * Checks if the error can be rendered.
     *
     * @param HorizonError $error
     * @return bool
     */
    private static function canRender(HorizonError $error)
    {
        // Always false when error rendering is disabled
        if (!config('errors.display_errors', true)) {
            return false;
        }

        // Handle silence operator (@)
        if (error_reporting() === 0) {
            if ($error->getLevel() < 5) {
                return false;
            }
        }

        // Only render errors that are above or equal to the configured severity
        if ($error->getLevel() < config('errors.display_sensitivity')) {
            return false;
        }

        // Render the error
        return true;
    }

    /**
     * Checks if the error should terminate the page.
     *
     * @param HorizonError $error
     * @return bool
     */
    private static function canTerminate(HorizonError $error)
    {
        return ($error->getLevel() >= 5);
    }

    /**
     * Gets the active error handler class as a string.
     *
     * @return ErrorHandlerInterface
     */
    private static function getErrorHandler()
    {
        $errorHandler = config('errors.handler', 'Horizon\Exception\ErrorHandler');

        // Ensure existence
        if (!class_exists($errorHandler)) {
            $errorHandler = 'Horizon\Exception\ErrorHandler';
        }

        // Use the default handler if the configured one isn't valid
        if (!((new $errorHandler) instanceof ErrorHandlerInterface)) {
            $errorHandler = 'Horizon\Exception\ErrorHandler';
        }

        return (new $errorHandler());
    }

}