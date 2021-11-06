<?php

/**
 * Configuration: App
 * Level: Easy
 *
 * This file provides basic settings for the application's environment.
 */

return array(

	/*
		Determines the timezone of the application. For best results, this should match the timezone of the database
		server configured for the application, but it isn't required.

		Available values:

			- See: http://php.net/manual/en/timezones.php

		Default value: 'America/Phoenix'
	*/
	'timezone' => env('app_timezone', 'UTC'),

	/*
		Determines if the framework will automatically redirect to directories when a page with the name of the directory
		is requested but not found. For example, if the directory /test/ exists but the user tries to load "/test" and
		no such page exists, they will be redirected (301) to "/test/".

		Available values:

			- true          Pages will be corrected to directories via redirect when applicable.
			- false         Do not redirect users to matching directories.

		Default value: true
	*/
	'redirect_to_directories' => true,

	/*
		Determines if template files will be cached after compilation. This can improve page load times for
		applications with a large number of template files being loaded at once. Turn this off if you're modifying
		template files, to ensure they render the latest version.

		Available values:

			- true          Store compiled templates in cache files.
			- false         Do not store template cache files.

		Default value: true
	*/
	'view_cache' => is_mode('production'),

	/*
		The current version of the application.
	*/
	'version' => '1.0',

);
