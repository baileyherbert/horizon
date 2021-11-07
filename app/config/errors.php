<?php

/**
 * This file configures default error handling behaviors for the application.
 */
return [

	/**
	 * Sets the class to use for error handling. This class will receive all uncaught errors and is responsible for
	 * displaying, logging, and reporting them.
	 */
	'handler' => 'Horizon\Exception\ErrorHandler',

	/**
	 * Sets whether errors should be printed to the output. When disabled, fatal errors will trigger a dedicated error
	 * page to be sent instead.
	 */
	'display_errors' => env('display_errors', is_mode('development')),

	/**
	 * Sets the severity level at which errors should be printed.
	 *
	 * - 1 => Shows errors, warnings, deprecations, strict violations, notices.
	 * - 2 => Shows errors, warnings, deprecations, strict violations.
	 * - 3 => Shows errors, warnings, deprecations.
	 * - 4 => Shows errors, warnings.
	 * - 5 => Shows errors.
	 */
	'display_sensitivity' => env('display_errors_level', 3),

	/**
	 * Sets whether errors should be logged to the `app/error_log` file.
	 */
	'log_errors' => env('error_logging', true),

	/**
	 * Sets the severity level at which errors should be logged.
	 *
	 * - 1 => Shows errors, warnings, deprecations, strict violations, notices.
	 * - 2 => Shows errors, warnings, deprecations, strict violations.
	 * - 3 => Shows errors, warnings, deprecations.
	 * - 4 => Shows errors, warnings.
	 * - 5 => Shows errors.
	 */
	'log_sensitivity' => env('error_logging_level', 3),

	/**
	 * Sets the severity level at which errors should be reported.
	 *
	 * - 1 => Shows errors, warnings, deprecations, strict violations, notices.
	 * - 2 => Shows errors, warnings, deprecations, strict violations.
	 * - 3 => Shows errors, warnings, deprecations.
	 * - 4 => Shows errors, warnings.
	 * - 5 => Shows errors.
	 */
	'report_sensitivity' => env('error_reporting_level', 4),

	/**
	 * Sets whether errors that occur within console commands are logged.
	 */
	'console_logging' => env('console_logging', is_mode('production')),

	/**
	 * Sets whether errors that occur within console commands are reported.
	 */
	'console_reporting' => env('console_logging', is_mode('production'))

];
