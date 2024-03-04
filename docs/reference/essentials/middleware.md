# Middleware

## Introduction

Middleware are classes that execute before controllers, but only if a route has been matched. This makes them very
useful for things like session authentication, permissions checking, rate limiting, and so on. Here's what a basic
middleware looks like:

```php
namespace App\Http\Middleware;

use Horizon\Http\Request;
use Horizon\Http\Response;
use Horizon\Http\Middleware;

class ExampleMiddleware extends Middleware {

    public function __invoke(Request $request, Response $response) {

    }

}
```

## Registering middleware

### Routes

You can define middleware from routing files like `/app/routes/web.php` using the middleware helper method.

Just like with controllers, a middleware can be in the format of `App\ClassName::methodName`, but the method name is
optional and if it is omitted then `__invoke` will be assumed.

```php title="app/routes/web.php"
Route::middleware('App\Http\Middleware\ExampleMiddleware');
Route::middleware('App\Http\Middleware\ExampleMiddleware::myMethod');
```

### Controllers

You can also return middleware as an array from the `getMiddleware` method of a controller. These middleware always
execute after those defined in the routing files.

```php title="app/src/Http/Controllers/ExampleController.php"
class ExampleController extends Controller {

    public function getMiddleware() {
        return array(
            'App\Http\Middleware\ExampleMiddleware',
            'App\Http\Middleware\ExampleMiddleware::myMethod'
        );
    }

}
```

## Parameter binding

Like controllers, middleware methods do not have any specific parameter requirements. Instead, the framework will use
reflection and the service container to bind and provide objects and values for your parameters in your own order. By
default, all of the following is available as parameters for middleware.

- The `Request` and `Response` instances
- The `Route` instance

This means given the following route:

```php
Route::get('/user/{name}/{tab?}');
```

Any of these method signatures will work, for example:

```php
public function __invoke();
public function __invoke(Request $request);
public function __invoke(Request $request, Response $response);
public function __invoke(Route $route);
```

!!! tip
	You can retrieve the request and response instances using the global `request()` and `response()` helpers, so those
	parameters are purely aesthetic.

## Techniques

Below are some common tricks and techniques used from middleware.

### Request attributes

You can set attributes on the `Request` instance from a middleware to pass it to the controller. This is particularly
useful because, for a controller, request attributes are able to be provided as parameters.

```php title="Middleware"
public function __invoke(Request $request) {
    // Session logic here
    $request->setAttribute('logged_in', !is_null($user));
    $request->setAttribute('user', $user);
}
```

```php title="Controller"
public function get(Request $request, Response $response, User $user, $logged_in) {
    $request->getAttribute('logged_in') == $logged_in; // true
    $request->getAttribute('user') == $user; // true
}
```

### Redirection

A middleware can redirect the page using the `redirect()` helper function. This will prevent the controller from
executing.

```php
public function __invoke(Request $request) {
    $uri = urlencode($request->getRequestUri());
    redirect('/account/login?redirect=' . $uri);
}
```

To allow the controller to continue executing despite the redirection, pass `false` into the third parameter of
`redirect`. This parameter determines whether to halt the page.

```php
redirect('/account/login?redirect=' . $uri, 302, false);
```

!!! danger
	Redirecting only sets the status code and `location` header on the response. It doesn't stop the controller, and
	won't prevent anything you've printed from being sent to the browser. Make sure to properly stop the controller
	when redirecting. A common pattern is to `return` the redirect directly like so:

	```php
		public function __invoke(Request $request) {
			if ($condition) {
				return redirect('/account/login?redirect=' . $uri);
			}

			// Do more work here
		}
	```

### Halting

A middleware can prevent the page from executing any further by "halting" it. This is done by calling the `halt` method
on the response instance.

```php
response()->halt();
```
