# Sessions

## Introduction

Use the `session()` global function to retrieve a session instance. Horizon takes care of session isolation and security
for you by automatically storing session data under a unique key, and by encrypting the contents of the session.

### Putting variables

The `put($key, $value)` method stores data in the session.

```php
session()->put('userId', 123);
```

### Retrieving variables

The `get($key, $default)` method retrieves data from the session.

```php
session()->get('userId');
```

### Pulling variables

The `pull($key, $default)` method retrieves data and then removes it from the session.

```php
session()->pull('userId');
```

### Forgetting variables

Remove data from the session with `forget($key)`.

```php
session()->forget('userId');
```

### Checking if variables exist

There are two methods to check if a session key exists.

```php
session()->has('key');
session()->exists('key');
```

The `has()` method returns true only if the key exists and is not null, whereas the `exists()` method will return true
even if the value is null.

## Flash storage

You can use sessions to temporarily store data for the next request. After the next request completes, the data is wiped. This is called flashing.

### Flashing

```php
session()->flash('error', 'This is some error text!');
```

### Reading flash

Retrieving flashed data is the same as any other session key - use the `get` method.

```php
$error = session()->get('error');

if ($error) {
    echo "We encountered an error in the last request: {$error}";
}
```

### Reflashing

To persist flashed data until the next request, you must reflash it. The `reflash` method will reflash _all_ flash in
the current session.

```php
session()->reflash();
```

#### Reflashing specific keys

To reflash only specific keys, use the `keep` method, and pass an array.

```php
session()->keep(array(
    'error'
));
```

## Session global

Use of the `$_SESSION` global is not recommended for accessing Horizon session data. This is because Horizon stores session
data under a key unique to its root directory, like so:

```php
$_SERVER = array(
    'horizon_5fee0cc5b9a8d4cf13be8b0ce28949fc' => array(
        'some_variable' => 'encrypted_data'
    )
)
```

However, for accessing sessions whose keys you know (for example, from another application on the same website), you can
and should use the `$_SESSION` global, as Horizon's session manager only allows access to its own sessions.

## CSRF protection

Protection against cross-site request forgery attacks is bundled with this framework, and the middleware required to
enable it is included in the stock `routes/web.php` file. By default, protection is only enforced against `POST`, `PUT`,
and `DELETE` request methods.

```php title="app/routes/web.php"
Route::middleware('Horizon\Http\Middleware\VerifyCsrfToken');
```

In order for this to work, you must include the csrf token in your forms. Views make this easy by providing a `@csrf`
function which outputs a hidden HTML input with the current token.

```html
<form action="" method="post">
    @csrf
    <input type="password" name="password">
</form>
```

You can also send the token as a header titled `X-CSRF-Token`, which is particularly useful for AJAX requests.

### Getting the token

Simply call the global function `csrf_token()` to get the current token as a string.

```php
$token = csrf_token();
```
