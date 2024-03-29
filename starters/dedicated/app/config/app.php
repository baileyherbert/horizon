<?php

/**
 * This file provides basic settings for the application's environment.
 */
return [

	/**
	 * Sets the timezone of the application. It's a good idea to use a consistent timezone across your application,
	 * server, and database.
	 *
	 * See: http://php.net/manual/en/timezones.php
	 */
	'timezone' => env('app_timezone', 'UTC'),

	/**
	 * Sets whether the framework's router will perform "trailing slash" redirects for routes that aren't found but
	 * have a trailing slash version available. The redirections are permanent (301).
	 */
	'redirect_to_directories' => true,

	/**
	 * Sets whether the framework should autoamtically cache view templates. This can provide a considerable boost to
	 * performance, but execution times will be negatively affected until the cache is fully built. To avoid this
	 * problem, you can run the `ace build` command to prebuild the cache before deploying to production.
	 */
	'view_cache' => is_mode('production'),

	/**
	 * Sets whether the framework should monitor template cache files for changes and automatically reload them. This
	 * will only work if the `view_cache` option is enabled. Increases runtime, disable in production.
	 */
	'view_cache_reload' => is_mode('development'),

	/**
	 * Sets paths to various core parts of the application. Paths which are not specified here will use their
	 * default values based on the `app` directory.
	 */
	'paths' => [
		'public' => 'public',
		'error_log' => is_mode('production') ? 'php://stderr' : 'error_log'
	]

];
