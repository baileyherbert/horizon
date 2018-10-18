<?php

namespace Horizon\Exception;

use Horizon\Http\Response;
use Exception;
use Horizon\Framework\Kernel;

class Handler
{

    /**
     * A list of the exception classes that should not be reported.
     *
     * @var string[]
     */
    protected $dontReport = array();

    /**
     * Report or log an exception. This can be configured to report anywhere (file, database, or a remote service).
     *
     * @param Exception $exception
     */
    public function report($exception)
    {
        if (method_exists($exception, 'report')) {
            $exception['report']();
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Response $response
     * @param Exception $exception
     */
    public function render(Response $response = null, $exception)
    {
        $reflect = new \ReflectionClass($exception);
        $shortName = $reflect->getShortName();
        $write = (function($message) use ($response) {
            if (is_null($response)) {
                echo $message;
            }
            else {
                $response->writeLine($message);
                $response->send();
            }
        });

        if ($exception instanceof \ErrorException) {
            $write(sprintf(
                '%s: %s in %s on line %d',
                static::friendlySeverity($exception->getSeverity()),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            return terminate();
        }

        if (method_exists($exception, 'render')) {
            $exception['render']($response);
            return terminate();
        }

        $write(sprintf(
            'Uncaught exception "%s" with message "%s" in %s on line %d: %s',
            $shortName,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));

        terminate();
    }

    /**
     * Gets the friendly form of a severity from bitwise form.
     *
     * @param int $severity
     * @return string
     */
    public static function friendlySeverity($severity)
    {
        static $severities = array(
            E_NOTICE => 'Notice',
            E_STRICT => 'Strict',
            E_PARSE => 'Parse error',
            E_WARNING => 'Warning',
            E_DEPRECATED => 'Deprecated',
            E_USER_ERROR => 'Error',
            E_USER_NOTICE => 'Notice',
            E_USER_WARNING => 'Warning',
            E_USER_DEPRECATED => 'Deprecated',
            E_ERROR => 'Fatal error'
        );

        if (isset($severities[$severity])) {
            return $severities[$severity];
        }

        return 'Unknown error';
    }

    /**
     * Checks if the exception should be ignored (for example, due to an '@' operator or configured error types).
     *
     * @param Exception $e
     * @return bool
     */
    protected function isValid(Exception $e)
    {
        if ($e instanceof \ErrorException) {
            return (bool)($e->getSeverity() & ini_get('error_reporting'));
        }

        return true;
    }

}