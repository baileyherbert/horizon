# Responses

## Getting the response instance

There are two primary ways to obtain a request instance. Here's a brief overview.

### Parameter binding

Declare a parameter with the `Response` type in your controller or middleware invoke methods. The framework will detect
it and supply the instance.

```php
function __invoke(Response $response) {}
```

### Helper function

There is a global `response()` helper function which returns the current instance.

```php
$response = response();
```

## Sending output

### Writing text directly

There are two methods for writing text directly to the response.

```php
$response->write('Hello world!');
$response->writeLine('Hello world!');
```

The `writeLine` method simply adds a new line character to the end. The `write` method can accept a string, boolean,
array, object, or null, and will convert them to strings if needed. Both methods accept an unlimited number of parameters
and will separate them with spaces:

```php
$response->writeLine('Hello', 'world'); // Hello world
```

### Rendering views

Use the `view()` helper function to render a view template file in the output. You can pass an array as the second
parameter containing environment variables.

```php
view('index.twig');
view('user/profile.twig', array(
    'username' => 'john.doe'
));
```

### Setting headers

You can write headers to the response, and also retrieve them.

```php
$response->setHeader('content-type', 'text/plain');
$response->getHeader('content-type');
```

## Redirection

Redirect from the current page to another using `redirect($to)`. An optional second parameter specifies the HTTP code
associated with the redirection, and is by default `302`.

Redirections whose target paths match routes will automatically be converted to legacy paths if currently enabled. See
[fallback routing](routing.md#fallback-routing) to learn more.

```php
$response->redirect('/some/page');
$response->redirect('https://mynewsite.com/', 301);
```

## Halting

The `halt()` method will prevent the current request from progressing further in the kernel. In other words, it won't
terminate the script, but it will prevent a controller from running if it's called from a middleware, for example.

```php
$response->halt();
```

## Context

Responses can store context variables which are exposed to views during rendering. For example, if you store the context
variable `user`, your view can accesss it using `{{ user }}`.

To store a context variable, we use the `with()` method.

```php
$response->with('user', 'john.doe');
```

!!! note
	These context variables can be overwritten by the variables sent directly to the views.
