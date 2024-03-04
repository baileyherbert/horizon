# Errors

## Error handling

Horizon has an error handling system which by default fully replicates the behavior of PHP's error handling. You
likely won't be able to tell the difference, but any errors or error logs you see from Horizon are rendered, logged, and
handled by Horizon.

It is possible – and pretty easy – to customize this behavior.

### Configuring errors

The `errors.php` configuration file has various options relating to error handling.

```php
// Sets the class to use for error reporting and displaying.
'handler' => 'Horizon\Exception\ErrorHandler',

// Determines whether errors in the framework or app will be displayed in detail in the response.
'display_errors' => true,

// Determines the error severity level at which errors should be displayed.
'display_sensitivity' => 4,

// Determines whether errors in the framework or app will be logged to the filesystem.
'log_errors' => true,

// Determines the error severity level at which errors should be logged.
'log_sensitivity' => 3,

// Determines whether the '@' operator can silence logging of errors.
'silent_logging' => true,

// Determines whethe the '@' operator can silence rendering of errors.
'silent_display' => true
```

### Custom error handler

To customize the handling of errors, create a class at `App\Exception\Handler` which extends `Horizon\Exception\Handler`.
You can override the default `report()` and `render()` methods to customize behavior.

```php
namespace App\Exception;

use Horizon\Framework\Application;
use Horizon\Exception\HorizonError;
use Horizon\Http\Exception\HttpResponseException;

class Handler extends \Horizon\Exception\Handler {

    public function http(HttpResponseException $ex) {
        // Handles an HTTP error. Default behavior is to have the HTTP kernel
        // show a matching error page:

        Application::kernel()->http()->error($ex->getCode());
    }

    public function render(HorizonError $error) {
        // Render the error
        echo sprintf(
            "%s: %s in %s on line %d\n",
            $error->getLabel(),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );
    }

    public function log(HorizonError $error) {
        // Log the error
    }

    public function report(HorizonError $error) {
        // Report the error
        // Make sure to call parent::report() if you override this method.

        parent::report($error);
    }

}
```

## Exceptions

### Framework exceptions

`Horizon\Exception\HorizonException` is a low-level exception which means an error occurred at the kernel level. The
code for such an exception will be one of the following.

|Code|Meaning|
|---|---|
|`0x1`|Missing required trait|
|`0x2`|Missing configuration file|
|`0x3`|Configuration file did not return an array|
|`0x4`|Driver load failed|
|`0x5`|Critical file missing|
|`0x6`|Critical class missing|
|`0x7`|Request instance not loaded|
|`0x8`|Response instance not loaded|
|`0x9`|View initialization error|

### Http response exceptions

The `Horizon\Http\Exception\HttpResponseException` defines an HTTP status code (4xx or 5xxx) which, if uncaught, is
shown to the user as an error page. You can override these error pages by creating an HTML file at `/app/errors/` with
the error code as the file name (e.g. `404.html`).

## Reporting exceptions

Any uncaught exception is sent to the error handler. If the exception has a method called `report()`, then this method
will be invoked. You can use this to implement special reporting logic on a per-exception basis.

```php
use Exception;
use Horizon\Exception\HorizonError;

class ReportingException extends Exception {

    public function report(HorizonError $error) {
        $syslog->reportException($this);
    }

}
```
