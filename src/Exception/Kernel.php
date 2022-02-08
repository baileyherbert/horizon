<?php

namespace Horizon\Exception;

use Horizon\Foundation\Application;
use Horizon\Support\Profiler;

/**
 * Kernel for exceptions and error handling.
 */
class Kernel {

	/**
	 * Boots the exception kernel and starts error handling.
	 */
	public function boot() {
		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', false);
		ini_set('log_errors', false);
		ini_set('error_log', Application::paths()->errorLog());

		$this->bindErrors();
		$this->bindExceptions();
		$this->bindFatal();
	}

	/**
	 * Calls the init method on the error handler.
	 */
	public function init() {
		Profiler::record('Boot error handler');

		$handler = ErrorMiddleware::getErrorHandler();
		if (method_exists($handler, 'init')) {
			$handler->init();
		}
	}

	/**
	 * Sends non-fatal errors to the error handler.
	 */
	private function bindErrors() {
		set_error_handler(function($severity, $message, $file, $line) {
			ErrorMiddleware::executeRuntimeError($severity, $message, $file, $line);
		});
	}

	/**
	 * Sends uncaught exceptions to the error handler.
	 */
	private function bindExceptions() {
		set_exception_handler(function($exception) {
			ErrorMiddleware::executeException($exception);
		});
	}

	/**
	 * Sends fatal errors to the error handler.
	 */
	private function bindFatal() {
		register_shutdown_function(function() {
			$error = error_get_last();

			if (!is_null($error)) {
				ErrorMiddleware::executeShutdownError($error['type'], $error['message'], $error['file'], $error['line']);
			}
		});
	}

}
