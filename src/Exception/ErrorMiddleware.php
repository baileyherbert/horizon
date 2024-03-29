<?php

namespace Horizon\Exception;

use Exception;
use Horizon\Foundation\Application;
use Horizon\Http\Exception\HttpResponseException;

class ErrorMiddleware {

	/**
	 * @var string|null
	 */
	public static $customHandler;

	/**
	 * Handles a runtime error (a standard error that occurs while the page is running).
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return void
	 */
	public static function executeRuntimeError($severity, $message, $file, $line) {
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
	public static function executeShutdownError($severity, $message, $file, $line) {
		$error = new HorizonError($message, $severity, $file, $line, 'shutdown');
		static::execute($error);
	}

	/**
	 * Handles an uncaught exception.
	 *
	 * @param Exception|Error $exception
	 * @return void
	 */
	public static function executeException($exception) {
		// Redirect console errors
		if (Application::environment() == 'console') {
			return Application::kernel()->console()->handleException($exception);
		}

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
	public static function execute(HorizonError $error) {
		$errorHandler = static::getErrorHandler();

		// Report the error
		if (static::canReport($error)) {
			call_user_func(array($errorHandler, 'report'), $error);
		}

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
				static::getErrorHandler()->http(new HttpResponseException(500));
			}

			abort();
		}
	}

	/**
	 * Logs and reports the given error silently, the caller assumes responsibility to terminate the page and show the
	 * error.
	 */
	public static function executeSilently(HorizonError $error) {
		// Redirect console errors
		if (Application::environment() == 'console') {
			return Application::kernel()->console()->handleException($error->getException());
		}

		$errorHandler = static::getErrorHandler();

		// Report the error
		if (static::canReport($error)) {
			call_user_func(array($errorHandler, 'report'), $error);
		}

		// Log the error
		if (static::canLog($error)) {
			call_user_func(array($errorHandler, 'log'), $error);
		}
	}

	/**
	 * Checks if the error can be reported.
	 *
	 * @param HorizonError $error
	 * @return bool
	 */
	public static function canReport(HorizonError $error) {
		// Handle silence operator (@)
		if (!(error_reporting() & $error->getSeverity())) {
			if ($error->getLevel() < 5) {
				return false;
			}
		}

		// Only report errors that are above or equal to the configured severity
		if ($error->getLevel() < config('errors.report_sensitivity', 4)) {
			return false;
		}

		// Report the error
		return true;
	}

	/**
	 * Checks if the error can be logged.
	 *
	 * @param HorizonError $error
	 * @return bool
	 */
	public static function canLog(HorizonError $error) {
		// Always false when error logging is disabled
		if (!config('errors.log_errors', true)) {
			return false;
		}

		// Handle silence operator (@)
		if (!(error_reporting() & $error->getSeverity())) {
			if ($error->getLevel() < 5) {
				return false;
			}
		}

		// Only log errors that are above or equal to the configured severity
		if ($error->getLevel() < config('errors.log_sensitivity', 3)) {
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
	public static function canRender(HorizonError $error) {
		// Always false when error rendering is disabled
		if (!config('errors.display_errors', true)) {
			return false;
		}

		// Handle silence operator (@)
		if (!(error_reporting() & $error->getSeverity())) {
			if ($error->getLevel() < 5) {
				return false;
			}
		}

		// Only render errors that are above or equal to the configured severity
		if ($error->getLevel() < config('errors.display_sensitivity', 3)) {
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
	public static function canTerminate(HorizonError $error) {
		return ($error->getLevel() >= 5);
	}

	/**
	 * Gets the current error handler object.
	 *
	 * @return ErrorHandlerInterface
	 */
	public static function getErrorHandler() {
		$errorHandler = config('errors.handler', 'Horizon\Exception\ErrorHandler');

		if (isset(static::$customHandler)) {
			$errorHandler = static::$customHandler;
		}

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
