<?php

/**
 * Configuration: Database
 * Level: Easy
 *
 * This file sets the target database settings for the framework to use when query building. If you are not using a
 * database, you can leave these settings as-is; they are only loaded and used when calling methods in the database
 * component of Horizon.
 */

return array(

	'main' => array(
		'host' => env('db_host', '127.0.0.1'),
		'database' => env('db_database', 'database'),
		'username' => env('db_username', 'root'),
		'password' => env('db_password', ''),

		/*
			The default character set for the database.
			Recommended value: 'utf8mb4'
		*/
		'charset' => env('db_charset', 'utf8mb4'),

		/*
			The default character set collation for the database.
			Recommended value: 'utf8mb4_unicode_ci'
		*/
		'collation' => env('db_collation', 'utf8mb4_unicode_ci'),

		/*
			A prefix to apply to table names. This can help isolate tables in a shared database. Leave blank to disable.
		*/
		'prefix' => env('db_prefix', ''),

		/*
			Determines whether query logging is enabled by default. This can use a large amount of memory in applications
			which run large amounts of queries, but can be used to identify slow queries in debugging.

			Recommended value: false
		*/
		'query_logging' => env('db_query_logging', false),

		/*
			Determines whether model objects loaded via the Horizon ORM will be cached in memory. This can prevent repeated
			queries and improve performance, but may use more memory depending on the number of objects.

			Recommended value: true
		*/
		'cache' => env('db_caching', true),

		/*
			Horizon supports the MySQLi, PDO, and MySQL extensions, and will automatically detect and use them in that order.
			However, you may wish to prioritize a certain driver. If the specified driver is available on the system, it will
			be preferred above any others. If it is not available on the system, the next best available driver will be used
			instead.

			Available options:

				- 'mysqli'      The mysql improved extension from PHP. Uses secure prepared statements.
				- 'pdo'         The PDO extension from PECL. Uses secure prepared statements.
				- 'mysql'       The legacy mysql extension from PHP. Uses real escaping.
				- 'none'        No preference, best available driver is used.

			Disclaimer: The 'mysql' extension does not support prepared statements and has been deprecated since PHP 5.5.
			Its usage should generally be avoided, but if the application uses Horizon's query builder, it will use real
			escaping to implement psuedo-prepared statements.

			Recommended value: 'mysqli'
		*/
		'preferred_driver' => env('db_driver', 'mysqli'),

		/**
		 * An array of directories, relative to the application's root directory, where migrations should be created in
		 * and loaded from. The `ace make:migration` command line tool will create new migrations in the first entry
		 * here.
		 */
		'migrations' => array(
			'app/database/migrations'
		)
	)

);
