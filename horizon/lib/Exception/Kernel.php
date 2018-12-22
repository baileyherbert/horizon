<?php

namespace Horizon\Exception;

/**
 * Kernel for exceptions and error handling.
 */
class Kernel
{

    /**
     * Boots the exception kernel and starts error handling.
     */
    public function boot()
    {
        $this->bindErrors();
        $this->bindExceptions();
        $this->bindFatal();
    }

    /**
     * Sends non-fatal errors to the error handler.
     */
    private function bindErrors()
    {
        set_error_handler(function($severity, $message, $file, $line) {
            ErrorMiddleware::executeRuntimeError($severity, $message, $file, $line);
        });
    }

    /**
     * Sends uncaught exceptions to the error handler.
     */
    private function bindExceptions()
    {
        set_exception_handler(function($exception) {
            ErrorMiddleware::executeException($exception);
        });
    }

    /**
     * Sends fatal errors to the error handler.
     */
    private function bindFatal()
    {
        register_shutdown_function(function() {
            $error = error_get_last();

            if (!is_null($error)) {
                ErrorMiddleware::executeShutdownError($error['type'], $error['message'], $error['file'], $error['line']);
            }
        });
    }

}
