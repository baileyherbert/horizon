<?php

/**
 * Configuration: Errors
 * Level: Advanced
 *
 * This file provides settings for error handling, reporting, and logging.
 */

return array(

    /*
        Sets the class to use for error reporting and displaying. If your application is using its own error handler,
        be sure to implement the Horizon\Exception\ErrorHandlerInterface, otherwise it will be ignored.

        Default value: 'Horizon\Exception\ErrorHandler'
    */
    'handler' => 'Horizon\Exception\ErrorHandler',

    /*
        Determines whether errors in the framework or app will be displayed in detail in the response. This is
        not recommended in a production environment because some errors and exceptions could potentially contain
        sensitive details.

        Available values:

            - true          Errors and exceptions will be included in the output.
            - false         Errors and exceptions will be silently ignored if possible.

        Default value: false
    */
    'display_errors' => true,

    /*
        Determines the error severity level at which errors should be displayed. Severity levels which are lower will not
        be displayed. This only applies if 'display_errors' is set to true.

        Available values:

            - 1             Shows errors, warnings, deprecations, strict violations, notices.
            - 2             Shows errors, warnings, deprecations, strict violations.
            - 3             Shows errors, warnings, deprecations.
            - 4             Shows errors, warnings.
            - 5             Shows errors.

        Default value: 4
    */
    'display_sensitivity' => 4,

    /*
        Determines whether errors in the framework or app will be logged to the filesystem. Errors are always logged
        to the 'app' directory of the framework.

        Available values:

            - true          Errors and exceptions will be logged.
            - false         Errors and exceptions will not be logged.

        Default value: true
    */
    'log_errors' => true,

    /*
        Determines the error severity level at which errors should be logged. Severity levels which are lower will not
        be displayed. This only applies if 'display_errors' is set to true.

        Available values:

            - 1             Shows errors, warnings, deprecations, strict violations, notices.
            - 2             Shows errors, warnings, deprecations, strict violations.
            - 3             Shows errors, warnings, deprecations.
            - 4             Shows errors, warnings.
            - 5             Shows errors.

        Default value: 3
    */
    'log_sensitivity' => 3,

    /*
        Determines the error severity level at which errors should be logged. Severity levels which are lower will not
        be displayed. This only applies if 'display_errors' is set to true.

        Available values:

            - 1             Shows errors, warnings, deprecations, strict violations, notices.
            - 2             Shows errors, warnings, deprecations, strict violations.
            - 3             Shows errors, warnings, deprecations.
            - 4             Shows errors, warnings.
            - 5             Shows errors.

        Default value: 3
    */
    'report_sensitivity' => 4,

    /*
        Determines whether code in the application can use the '@' operator to silence their errors. If enabled, any
        errors occurring from code using this operator will not be logged. However, any fatal, page-breaking errors
        will still be  logged if 'log_errors' is set to true.

        Available values:

            - true          Silenced errors and exceptions will be logged.
            - false         Silenced errors and exceptions will not be logged.

        Default value: true
    */
    'silent_logging' => true,

    /*
        Determines whether code in the application can use the '@' operator to silence their errors. If enabled, any
        errors occurring from code using this operator will not be displayed. However, any fatal, page-breaking errors
        will still be displayed if 'display_errors' is set to true.

        Available values:

            - true          Silenced errors and exceptions will be rendered.
            - false         Silenced errors and exceptions will not be rendered.

        Default value: true
    */
    'silent_display' => true,

    /*
        Determines whether code in the application can use the '@' operator to silence their errors. If enabled, any
        errors occurring from code using this operator will not be reported. However, any fatal, page-breaking errors
        will still be reported.

        Available values:

            - true          Silenced errors and exceptions will be reported.
            - false         Silenced errors and exceptions will not be reported.

        Default value: true
    */
    'silent_reporting' => true,

    /*
        Determines whether errors that occur within a console command are logged.

        Available values:

            - true          Console command errors are logged.
            - false         Console command errors are not logged.

        Default value: true
     */
    'console_logging' => true,

    /*
        Determines whether errors that occur within a console command are reported.

        Available values:

            - true          Console command errors are reported.
            - false         Console command errors are not reported.

        Default value: true
     */
    'console_reporting' => true,

);
