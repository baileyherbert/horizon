<?php

namespace Horizon\Exception;

class ErrorHandler implements ErrorHandlerInterface
{

    /**
     * Renders the specified error to the screen. This is only called if error displaying is enabled. An error renderer
     * should not manually halt or kill the page; this will be done automatically by the kernel if the error severity
     * is appropriate.
     *
     * @param HorizonError $error
     * @return void
     */
    public function render(HorizonError $error)
    {
        echo sprintf(
            "<strong>%s</strong>: %s in <strong>%s</strong> on line <strong>%d</strong> <br>",
            $error->getLabel(),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );
    }

    /**
     * Logs the specified error. This is only called if logging is enabled in the errors configuration file.
     *
     * @param HorizonError $error
     * @return void
     */
    public function log(HorizonError $error)
    {
        $logFile = ini_get('error_log');
        $logDir = dirname($logFile);

        // Do not attempt to write if the file cannot be created
        if (!file_exists($logDir)) {
            return;
        }

        // Do not attempt to write if we don't have permissions to the file

        if ((file_exists($logFile) && !is_writable($logFile)) || (!file_exists($logFile) && !is_writable($logDir))) {
            return;
        }

        // Generate the message
        $timestamp = date('d-M-Y G:i:s T');
        $message = $this->formatMessage(sprintf(
            "[%s] Horizon: %s:  %s in %s on line %d\r\n",
            $timestamp,
            $error->getLabel(),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        ));

        // Write the log message
        @file_put_contents($logFile, $message, FILE_APPEND);
    }

    /**
     * Reports the specified error.
     *
     * @param HorizonError $error
     * @return void
     */
    public function report(HorizonError $error)
    {
        $originalException = $error->getException();

        if (!is_null($originalException)) {
            if (method_exists($originalException, 'report')) {
                call_user_method('report', $originalException, $error);
            }
        }
    }

    /**
     * Formats the log message to add helpful whitespace on stack traces.
     *
     * @param string $message
     * @return string
     */
    private function formatMessage($message)
    {
        $message = preg_replace("/\r?\n/", "\n", $message);
        $lines = explode("\n", $message);

        foreach ($lines as $i => $line) {
            if ($i > 0 && !empty($line)) {
                $lines[$i] = "    " . $line;
            }
        }

        return implode("\r\n", $lines);
    }

}