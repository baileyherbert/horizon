<?php

return [
	'main' => [
		'host' => env('db_host', '127.0.0.1'),
		'port' => env('db_port', 3306),
		'database' => env('db_database', 'database'),
		'username' => env('db_username', 'root'),
		'password' => env('db_password', ''),
		'charset' => env('db_charset', 'utf8mb4'),
		'collation' => env('db_collation', 'utf8mb4_unicode_ci'),
		'prefix' => env('db_prefix', ''),
		'query_logging' => env('db_query_logging', false),
		'cache' => env('db_caching', true),
		'preferred_driver' => env('db_driver', 'none'),
		'send_timezone' => env('db_send_timezone', true)
	]
];
