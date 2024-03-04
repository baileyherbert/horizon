# Routing

## Defining routes

By default, the `/app/routes/web.php` file is loaded into the framework to initialize routes. You can edit this file and
add your own routes. You may also optionally create additional files under `/app/routes/` to keep organized, but these
will need to be manually included.

```php title="app/routes/web.php"
<?php

Route::middleware('Horizon\Http\Middleware\VerifyCsrfToken');
Route::any('/', 'App\Welcome', 'index.php');
```

## Request methods

In the default code above, `any` is used to match all requests for the home page (`/`) to the `App\Welcome` controller.
However, we can narrow down a route to a specific request method. For example:

```php
Route::get($path, $controller);
Route::post($path, $controller);
```

There are many methods available: `get`, `post`, `put`, `patch`, `delete`, `options`, and `any`. You can also match
multiple methods simultaneously using `match`.

```php
Route::match(array('get', 'post'), $path, $controller);
```

## Controllers

For route methods shown above, the `$controller` parameter accepts a callable. For strings, a full class name with an
optional method name is expected, like so:

- `App\Http\Controllers\ControllerName`
- `App\Http\Controllers\ControllerName::methodName`

If you don't specify a method name then `__invoke` is assumed. For more details on controllers, see the
[controllers](controllers.md) documentation.

Any type of callable works for the controller parameter, including anonymous functions.

```php
Route::get('/account/login', 'App\Http\Controllers\Login::get');
Route::post('/account/login', 'App\Http\Controllers\Login::post');

Route::get('/user/{username}', function(Response $response, $username) {
    $response->write('Hello', $username);
});
```

## Defining action routes

Action routes are special types of routes with default controllers which perform a special action. Horizon comes built in
with two action routes.

### View routes

If you want to serve pages which do not require controller logic, you can define a view route, which links the path directly to a view
instead of a controller instance.

```php
Route::view('/errors/not-found', 'not-found.twig', $variables);
```

The above example will link the `/errors/not-found` page to the `not-found.twig` template file, passing the `$variables`
array for rendering. The third argument is optional.

### Redirect routes

For quick redirection from one page to another, redirect routes are available without the need for any controllers.

```php
Route::redirect('/original/page', '/new/page', 301);
```

The above example will redirect `/original/page` to `/new/page` with an `HTTP 301` response code. The third parameter is optional and
defaults to 302 (temporary redirect).

!!! note
	Action routes will still execute any middleware defined before them.

## Attributes

Attributes are extra parameters applied to routes, such as middleware or a prefix. They can be applied to individual
routes or a group. This section will explain how to use them individually, and then the
[groups](#groups) section further below will explain how to use them as groups.

Note that attributes only apply to routes defined **after** them, so if you want a middleware to execute for all routes,
put it at the very top or put all routes in a middleware group.

### Middleware

The `middleware` attribute the router that you wish to **execute a middleware** before your routes.

```php
Route::middleware('App\Http\Middleware\MyMiddlewareClass');
​
Route::view('/home', 'home.twig');
Route::view('/about', 'about.twig');
```

### Prefix

The `prefix` attribute the router to assign a **prefix** to all subsequent route names.

```php
Route::prefix('/user/{username}');
​
# /user/{username}/profile
Route::view('/profile', 'user/profile.twig');
​
# /user/{username}/badges
Route::view('/badges', 'user/badges.twig');
```

### Name prefix

The `name` attribute tells the router to assign a **name prefix** to all subsequent route names. This only applies to
routes that you explicitly declare a name on.

```php
Route::name('admin.');
​
# The following route will be named 'admin.dashboard'.
Route::get('/', 'App\Http\Controllers\Admin\Dashboard')->name('dashboard');
```

### Domain

The `domain` attribute tells the router that the subsequent routes only apply to a **certain domain**. If you specify
multiple domain attributes, the old calls will expire, rather than stacking.

```php
Route::domain('{username}.blog.com');
​
# The following route will only apply to {username}.blog.com.
Route::view('/', 'profiles/home.twig');
```

## Groups

### Containers

Groups are containers which isolate the routing environment. In other words, if you define a middleware attribute from
within a group, it will only apply to routes in that group. To define a standard group, use the `group` method.

```php
Route::group(function() {
    Route::prefix('/subdir');
    Route::get('/home', ...); // points to /subdir/home
});

Route::get('/home', ...); // points to /home
```

### Attribute groups

You can pass a closure in the second argument of [attribute methods](#attributes) to quickly create a group with that
attribute applied.

```php
Route::prefix('/subdir', function() {
    Route::get('/home', ...); // points to /subdir/home
});
​
Route::name('prefixed', function() { ... });
Route::middleware('Some\Middleware', function() { ... });
Route::domain('{user}.mysite.com', function() { ... });
```

## Variables

Route paths support variables which can be retrieved from the Request instance or from an argument in the controller. Optional variables
can be specified using `?`.

```php title="app/routes/web.php"
Route::get('/user/{username}/{tab?}', 'App\Http\Controllers\UserProfile');
```

The above code sample will match both of the following requests.

- `/user/john`
- `/user/john/friends`

### Pattern constraints

Instead of manually validating variables, you can optionally define a regular expression constraint. Routes will only
be matched if the variable fits the regular expression. To do this, use the `where` method on the route.

```php
Route::get('/user/{username}', 'App\User')->where('username', '\w+');
```

The sample above uses `\w+`, which is equivalent to `[a-zA-Z0-9_]`.

- `/user/john_doe` will match the route
- `/user/john!doe` will not

### Default values

While you can specify the default value of an optional variable in your controller, it's also possible using the
`defaults` method on the route.

```php
Route::get('/docs/{page}', 'App\Docs')->defaults('page', 'index');
```

## Fallback routing

Routing relies on rewrite rules. If rewrite rules are not available on the server, then routing will not work. Since
Horizon is dedicated to ensuring maximum compatibility with servers, there is a backup in place for cases like this.

You can use the `fallback` method on a route to specify a `.php` file which will load that route. When rewrite rules
are disabled, the framework will detect this and will use these files instead. Generated links, redirections, etc
will all be seamlessly converted to using the `.php` files.

```php title="app/routes/web.php"
# index.php  ->  /
Route::get('/', $callable)->fallback('/index.php');

# user/profile.php?username=bob  ->  /user/bob
Route::get('/user/{username}', $callable)->fallback('/user/profile.php');
```

Fallback files must exist on the server and only need to require the `legacy.php` bootstrap file. The framework will
take care of the rest.

```php title="user/profile.php"
<?php
require_once 'horizon/bootstrap/legacy.php';
```

## Using multiple route files

The framework will only load the `app/route/web.php` file automatically. Other files in the same directory will not
be loaded by default.

You can load additional files with route declarations using the `load($fileName)` method. The file provided is loaded
relative to the `app/routes` directory and should not end with `.php`.

```php
# Loads routes in /app/routes/admin.php
Route::load('admin');
```

!!! note
	If you load an additional file from within a group, the group's attributes will be applied to all routes loaded from
	the additional file.
