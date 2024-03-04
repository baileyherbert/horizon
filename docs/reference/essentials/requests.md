# Requests

## Getting the request instance

There are two primary ways to obtain a request instance. Here's a brief overview.

### Parameter binding

Declare a parameter with the `Request` type in your controller or middleware invoke methods. The framework will detect
it and supply the instance.

```php
function __invoke(Request $request) {}
```

### Helper function

There is a global `request()` helper function which returns the current instance.

```php
$request = request();
```

## Request information

### Get the request path

Gets the path for the request. This always starts with a preceding forward slash (/). For example: "/path/to/file"

```php
$request->path();
```

### Get the request URL

Gets the absolute URL for the request, without query parameters. The optional `$path` parameter will generate a URL
for another path.

```php
$request->url();
// https://www.domain.com/path/to/file

$request->url('/some/page');
// https://www.domain.com/some/page
```

### Get the full URL

Gets the absolute URL for the request, including encoded query parameters.

```php
$request->fullUrl();
// https://www.domain.com/path/to/file?some=query
```

### Get the root URL

Gets the absolute URL to the root of the current domain.

```php
$request->root();
// https://www.domain.com/
```

### Check if the request is from AJAX

Checks whether this request was initiated from AJAX.

```php
if ($request->ajax()) {
    ...
}
```

### Check if the request is secure

Checks whether this request is transmitted over HTTPS.

```php
if ($request->secure()) {
    ...
}
```

### Get the remote address

Returns the client's IP address. Beware, this can be in either IPv4 or IPv6 format.

```php
$request->ip();
```

### Get the user agent

Returns the client's user agent string.

```php
$request->userAgent();
```

### Get headers

Returns the value of the specified header as a string, or null.

```php
$request->header('User-Agent');
```

## User input

### Get a query value

Retrieve a query value from the request URI using the `query` method. The second parameter specifies the return value if
the query was not found, which is `null` by default.

```php
$id = $request->query('id');
$tab = $request->query('tab', 'default');
```

### Get a posted value

Retrieve a posted value from a form using the `post` method. The second parameter specifies the return value if
the value was not found, which is `null` by default.

```php
$email = $request->post('email');
```

### Get an input value

The `input` method checks for values from both `post` and `query`, in that order. The first match is returned, or the
second parameter (`null`) if no matches were found.

```php
$email = $request->input('email');
```

### Get a JSON value

For JSON requests, you can easily parse the incoming data using the `json($key, $default)` method.

```php
// Parse the full body
$json = $request->json();

// Get a specific key
$json = $request->json('path.to.key');

// Get any errors in the JSON as a string
$error = $request->jsonError();
```

## Files

### Get an uploaded file

To obtain a specific file, use the `file($key)` method. If there is a file with that name, an
[UploadedFile](https://api.symfony.com/4.1/Symfony/Component/HttpFoundation/File/UploadedFile.html) instance is returned.
Otherwise, `null` is returned.

```php
$file = $request->file('avatar');

if (!is_null($file)) {
    if ($file->isValid()) {
        $file->getRealPath();
        $file->move('/var/uploads/', 'newfilename.png');
    }
    else {
        $response->write('Upload error!', $file->getError());
    }
}
```

## Attributes

Attributes store request data in memory. This is useful for sending data from middleware to controllers, for example.

```php
$request->setAttribute('user', ['username' => 'john']);
$request->getAttribute('user');
```

## Sessions

You can access a session instance using the `session()` method or global helper function. For documentation on how to
use sessions, see the [sessions page](sessions.md).

```php
$session = $request->session();
$session = session();
```

## Advanced

It's worth noting that the `Horizon\Http\Request` class extends `Symfony\Component\HttpFoundation\Request`. For further
documentation and advanced usage, consult the
[Symfony Request](https://api.symfony.com/4.0/Symfony/Component/HttpFoundation/Request.html) documentation.
