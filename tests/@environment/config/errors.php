<?php

return [
	'handler' => 'Horizon\Exception\ErrorHandler',
	'display_errors' => env('display_errors', is_mode('development')),
	'display_sensitivity' => env('display_errors_level', 3),
	'log_errors' => env('error_logging', true),
	'log_sensitivity' => env('error_logging_level', 3),
	'report_sensitivity' => env('error_reporting_level', 4),
	'console_logging' => env('console_logging', is_mode('production')),
	'console_reporting' => env('console_logging', is_mode('production'))
];
