<?php

/**
 * Configuration: Session
 * Level: Advanced
 *
 * This file allows you to customize how Horizon's HTTP component manages user sessions. It is important to configure
 * these settings properly in shared hosting environments if you're storing sensitive information.
 *
 * Horizon automatically generates a unique 'key' for its sessions in which all session data is placed under. This
 * means if you have other applications using PHP sessions on the same website, they will not conflict.
 */

return array(

    /*
        Sets the public session name. This is visible to users from within their browser's Inspect Element panel
        or via scripts, but isn't significant. Must contain only letters and numbers, and cannot consist entirely
        of digits.

        Warning: Changing this value will delete all sessions under the old value. Data will not transfer, so
        changing this in production isn't a good idea.

        Default value: 'Horizon'
    */
    'name' => env('session_name', 'Horizon'),

    /*
        Determines which driver to use for storing persistent user data between pageloads.
        Available values:

            - 'cookie'      Stores the session in a secure PHP session which is persisted using cookies.
            - 'database'    Stores the session in the sessions table of the configured mysql database.
            - 'array'       Stores the session in memory via a PHP array. This does not persist.

        Default value: 'cookie'
    */
    'driver' => env('session_driver', 'cookie'),

    /*
        Determines whether to encrypt the data payload stored within the session. While this may not be necessary
        on a private server, it's still probably a good idea. Session drivers use symmetric key encryption which is
        lightweight, fast, and has no extension or server requirements. The key is configured automatically.

        Changing this value will automatically convert existing sessions to encrypted/decrypted format, if needed.

        Available values:

            - true          Enables automatic encryption and decryption of session payloads.
            - false         Does not encrypt the session payloads.

        Default value: true
    */
    'encrypt' => env('session_encryption', true)

);
