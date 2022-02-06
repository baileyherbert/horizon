<?php

/**
 * This file configures how the application manages sessions.
 */
return array(

	/**
	 * Sets the name for the session cookie.
	 */
	'name' => env('session_name', 'Horizon'),

	/**
	 * Sets whether the session cookie contents will be encrypted before saving them to the disk. This protects the
	 * contents of the cookie from being read or modified by other users on the server. Please set the `APP_SECRET`
	 * environment variable to a secure, random string to strengthen this encryption.
	 */
	'encrypt' => env('session_encryption', true),

	/**
	 * Sets the driver to use for sessions.
	 *
	 * 	- 'cookie'     Stores session data within PHP session cookie files.
	 * 	- 'database'   Stores session data in the database.
	 *
	 * When using the `database` driver, please make sure you've added the necessary migration into your project by
	 * running the following commands:
	 *
	 *   - ace make:migration create_sessions_table --template sessions
	 *   - ace migration:run
	 */
	'driver' => 'cookie',

	/**
	 * Sets the target maximum lifetime of sessions (in seconds). The actual implementation and reliability of this
	 * will vary depending on the driver being used.
	 */
	'lifetime' => 7200,

	/**
	 * Sets whether session cookie contents will be serialized with the `serialize()` function. This allows most data
	 * types to be stored, but poses a security risk if data written to the session isn't controlled. When disabled,
	 * session cookies will store data using `json_encode()` instead.
	 *
	 * Note: Changing this will cause all existing sessions to become invalidated. The cookie driver will reset the
	 * affected cookies automatically.
	 */
	'serialize' => true

);
