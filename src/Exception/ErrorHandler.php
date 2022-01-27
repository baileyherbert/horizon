<?php

namespace Horizon\Exception;

use Horizon\Foundation\Application;
use Horizon\Http\Exception\HttpResponseException;

class ErrorHandler implements ErrorHandlerInterface {

	/**
	 * Called automatically by the framework when the exception kernel is booted. Useful for initializing a global
	 * error reporting service.
	 */
	public function init() {

	}

	/**
	 * Handles an HTTP exception. The default behavior is to show a matching error page.
	 *
	 * @param HttpResponseException $ex
	 * @return void
	 */
	public function http(HttpResponseException $ex) {
		Application::kernel()->http()->error($ex->getCode(), $ex->getMessage());
	}

	/**
	 * Renders the specified error to the screen. This is only called if error displaying is enabled. An error renderer
	 * should not manually halt or kill the page; this will be done automatically by the kernel if the error severity
	 * is appropriate.
	 *
	 * @param HorizonError $error
	 * @return void
	 */
	public function render(HorizonError $error) {
		if (response()->isJson()) {
			$message = $error->getLabel() . ': ' . $error->getMessage();
			$stackIndex = strpos($message, ' Stack trace: ');
			$stack = [];

			if ($stackIndex !== false) {
				$trace = substr($message, $stackIndex + 14);
				$stack = preg_split("/\r?\n/", $trace);
				$message = substr($message, 0, $stackIndex);
			}

			$file = $error->getFile() . ':'. $error->getLine();
			$messageFile = ' in ' . $file;

			if (ends_with($message, $messageFile)) {
				$message = substr($message, 0, -strlen($messageFile));
			}

			response()->setStatusCode(500);
			response()->setContent(json_encode(
				[
					'status' => 500,
					'error' => 'Internal Server Error',
					'message' => $message,
					'file' => $file,
					'stack' => $stack
				],
				JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
			));

			Application::kernel()->http()->close(true);
		}

		$message = !$this->useHtml() ? "%s: %s in %s on line %d\n"
						: "<strong>%s</strong>: %s in <strong>%s</strong> on line <strong>%d</strong> <br>\n";

		echo sprintf(
			$message,
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
	public function log(HorizonError $error) {
		$logFile = ini_get('error_log');
		$logDir = dirname($logFile);

		// Always allow php:// paths
		if (!starts_with($logFile, 'php://')) {
			// Do not attempt to write if the file cannot be created
			if (!file_exists($logDir)) {
				return;
			}

			// Do not attempt to write if we don't have permissions to the file
			if ((file_exists($logFile) && !is_writable($logFile)) || (!file_exists($logFile) && !is_writable($logDir))) {
				return;
			}
		}

		// Generate the message
		$timestamp = date('d-M-Y G:i:s T');
		$message = $this->formatMessage(sprintf(
			"[%s] Horizon: %s: %s in %s on line %d\r\n",
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
	public function report(HorizonError $error) {
		$originalException = $error->getException();

		if (!is_null($originalException)) {
			if (method_exists($originalException, 'report')) {
				call_user_func(array($error, 'report'));
			}
		}
	}

	/**
	 * Formats the log message to add helpful whitespace on stack traces.
	 *
	 * @param string $message
	 * @return string
	 */
	private function formatMessage($message) {
		$message = preg_replace("/\r?\n/", "\n", $message);
		$lines = explode("\n", $message);

		foreach ($lines as $i => $line) {
			if ($i > 0 && !empty($line)) {
				$lines[$i] = "    " . $line;
			}
		}

		return implode("\r\n", $lines);
	}

	/**
	 * Checks the environment and returns true if error messages should be displayed in HTML.
	 *
	 * @return bool
	 */
	private function useHtml() {
		return (Application::environment() == 'web');
	}

}
