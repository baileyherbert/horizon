# Controllers

## Introduction

When a route is matched to the current request, a controller will be called to fulfill the request. This is the
fundamental purpose of controllers – to perform any backend logic and send back the output. A basic controller will
look something like this:

```php title="app/src/Http/Controllers/Home.php"
namespace App\Http\Controllers;

use Horizon\Http\Controller;
use Horizon\Http\Request;
use Horizon\Http\Response;

class Home extends Controller {

	public function __invoke(Request $request, Response $response) {
        view('home');
    }

}
```

## Registering controllers

The only way to register a controller for automatic execution is to assign it to a route from your routing configuration
file. To learn more about how that works, see the [routing documentation](routing.md).

```php title="app/routes/web.php"
Route::get('/', 'App\Http\Controllers\Home');
```

## Parameter binding

Like middleware, controller methods do not have any specific parameter requirements. Instead, the framework will use
reflection and the service container to bind and provide objects and values for your parameters in your own order. By
default, all of the following is available as parameters for middleware.

- The `Request` and `Response` instances
- The `Route` instance
- All request attributes
- All route variables

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
public function __invoke(Response $response, $name, $tab = 'profile');
```

!!! tip
	You can retrieve the request and response instances using the global `request()` and `response()` helpers, so those
	parameters are purely aesthetic.

## Initialization

If you need to execute code for an entire controller, regardless of which method is invoked, you can use the `init`
method. This is called immediately before the controller method and can also bind parameters as specified above.

```php
public function init($userId) {
    $this->user = User::findOrFail($userId);
}
```

## Middleware

Controllers can also instruct the framework to execute middleware before its execution, using an overridable method
named `getMiddleware`. This method can return an array of class names or a single class name as a string.

```php
public function getMiddleware() {
    return array(
        'App\Http\Middleware\RunMeFirst'
    );
}
```

!!! note
	Middleware defined in the routes config will still execute, and any duplicates will only run once.
