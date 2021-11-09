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
	 * Sets whether session cookie contents will be serialized with the `serialize()` function. This allows most data
	 * types to be stored, but poses a security risk if data written to the session isn't controlled. When disabled,
	 * session cookies will store data using `json_encode()` instead.
	 *
	 * Note: Changing this will cause all existing sessions to become invalidated. The cookie driver will reset the
	 * affected cookies automatically.
	 */
	'serialize' => true

);
