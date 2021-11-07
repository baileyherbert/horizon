<?php

/**
 * This file configures the databases that the application can use.
 */
return [

	/**
	 * The `main` connection is the default connection that will be used across the application. You can add additional
	 * connections by giving them different names.
	 */
	'main' => [

		/**
		 * The connection details for the database. It is recommended to set these using environment variables.
		 */
		'host' => env('db_host', '127.0.0.1'),
		'port' => env('db_port', 3306),
		'database' => env('db_database', 'database'),
		'username' => env('db_username', 'root'),
		'password' => env('db_password', ''),

		/**
		 * The default character set for the database connection.
		 */
		'charset' => env('db_charset', 'utf8mb4'),
		'collation' => env('db_collation', 'utf8mb4_unicode_ci'),

		/**
		 * The prefix to use for tables. This can be used to isolate tables in a shared database.
		 */
		'prefix' => env('db_prefix', ''),

		/**
		 * Sets whether to log queries for this database. This log can be retrieved at runtime to help track down
		 * problems or to detect slow queries, but it can also use a large amount of memory, so it's recommended to
		 * leave it off on production.
		 */
		'query_logging' => env('db_query_logging', false),

		/**
		 * Sets whether model objects will be cached under their primary keys when loaded through this database. This
		 * can grant a massive performance boost when frequently querying by primary key, but will use more memory.
		 */
		'cache' => env('db_caching', true),

		/**
		 * Sets the preferred driver to use for this connection. The default value (`none`) will automatically choose
		 * from the best available driver.
		 *
		 * 	- 'mysqli'    The mysql improved extension from PHP.
		 * 	- 'pdo'       The PDO extension from PECL.
		 * 	- 'mysql'     The legacy deprecated mysql extension.
		 * 	- 'none'      No preference, best available driver is used.
		 *
		 * Disclaimer: The 'mysql' driver does not support prepared statements and has been deprecated since PHP 5.5.
		 * Its usage should be avoided, but Horizon's query builder will use real escaping to implement psuedo-prepared
		 * statements.
		 */
		'preferred_driver' => env('db_driver', 'none'),

		/**
		 * Sets whether to apply the application's timezone to this database connection. This will execute as a single
		 * query and will slow down database initialization variably based on latency. If you know that your database
		 * server and application share the same time zone, disable this.
		 */
		'send_timezone' => true
	]

];
