# Templates

## Introduction

Horizon's templates are built on top of [Twig](https://twig.symfony.com) and have been modified to add custom syntax and
automatic translations. The custom syntax is very similar to Blade to maximize compatibility with Blade templates.

## Syntax

### Logic and conditionals

You can perform conditional logic using the `@if`, `@elseif`, and `@else` tags.

```html
<div class="greeting">
    @if ($user->role == "admin")
        You're logged in as an administrator.
    @elseif ($user->authenticated)
        Welcome back!
    @else
        Hey there, visitor!
    @end
</div>
```

Most logic operators work as expected, like `!`.

```
@if (!empty($text) && strlen($text) < 5)
```

### Looping and iterations

You can use a classic `for` loop to iterate over a series of numbers.

```
@for ($x = 0; $x < 5; $x++)
    This is line number {{ $x }}.
@end
```

You can also iterate over arrays using `foreach`.

```
@foreach ($array as $value)
@foreach ($array as $index => $value)
```

### Printing variables

You can output variables or functions by enclosing them in curly brackets.

```
{{ $variable }}
{{ strlen($variable) }}
```

This output is automatically escaped for HTML output. If you don't want to escape, you can alternatively use the below
syntax.

```
{!! $variable !!}
```

Ternary operators are supported, and only the positive value is required.

```
{{ $bool ? "true" }}
{{ $bool ? "true" : "false" }}
```

You can retrieve a member from an object or array using the `->` operator.

```
$array->value
$function->method()
```

### Comments

You can use `{#` and `#}` to define a comment in a template file.

### Other syntax

For now, that is the limit of our custom syntax. You can also use Twig syntax at any point throughout the template.
Ultimately, the custom syntax above is transpiled to Twig syntax in the end.

```twig
<div class="container">
    {% if (user.authenticated) %}
        ...
    {% endif %}
</div>
```

## Helpers

### Generating a CSRF token input

The `@csrf` method will output a hidden input field containing the current token.

```twig
<form action="" method="post">
    @csrf
    <input type="text" name="username">
</form>
```

### Enabling automatic localization

The `@translate` method specifies a namespace to use for automatically translating the view. Calling it multiple times
will add additional namespaces, rather than overriding them. Ideally, these belong at the top of the file.

```twig
@translate('some.namespace')
@translate('some.other.namespace')

<strong>Twig template here</strong>
```

### Translating strings

The `@__` method will return the translation of the specified text. It is not bound to any namespaces. If you think `__`
is ugly like I do, you can also use the alias `@localize`.

```twig
<strong>This is a @__('Twig template')!</strong>
```

### Generating links

The `@link` method will generate a link to the given path. The resulting link will be matched to a route and manipulated
to ensure it points to within the application's root directory. This also will fall back to `.php` files if rewrite
rules aren't supported.

```twig
<strong>Back to <a href="@link('/')">home page</a>.</strong>
```

### Including other template files

The `@include` method is a shortcut for including another template file at the given path. The path is **not** relative
to the current working directory.

```twig
@include('header');
```

### Extending a layout

The `@extend` method is a shortcut for extending another template file at the given path.

```twig
@extend('layout');
```

The `@section` method creates a section boundary in a template. If you're extending a template, this will replace a
section with a matching name.

```twig
@section('content')
<strong>This is my content!</strong>
@end
```

## Functions

You have access to a variety of functions from within template files.

### Native functions

These function calls are forwarded directly to their PHP counterparts, so they work exactly as you'd expect them to.

- `rand`
- `md5`
- `sha1`
- `trim`
- `ltrim`
- `rtrim`
- `explode`
- `implode`
- `strlen`
- `substr`
- `ucfirst`
- `ucwords`
- `sprintf`
- `str_repeat`
- `str_word_count`
- `strpos`
- `stripos`

### Helper functions

These functions are ports of Horizon's global helpers.

- `camel_case`
- `kebab_case`
- `snake_case`
- `title_case`
- `studly_case`
- `starts_with`
- `ends_with`
- `str_before`
- `str_after`
- `str_contains`
- `str_finish`
- `str_is`
- `str_limit`
- `str_plural`
- `str_random`
- `str_replace_first`
- `str_replace_last`
- `str_singular`
- `str_slug`
- `str_start`
- `str_substring`
- `str_length`
- `str_find`
- `str_ucfirst`
- `str_upper`
- `str_lower`
- `array_get`
- `array_has`
- `array_first`
- `array_last`
- `array_random`
- `head`
- `last`
- `abort`
- `bcrypt`
- `blank`
- `config`
- `csrf_token`
- `session`
- `config`

### Filter functions

We also have a variety of filters that can be used as functions. When you call these like functions, they will get
transpiled into the appropriate Twig filters.

- `length`
- `count`
- `abs`
- `batch`
- `ucfirst`
- `capitalize`
- `date`
- `default`
- `e`
- `escape`
- `first`
- `array_shift`
- `last`
- `array_pop`
- `format`
- `join`
- `implode`
- `json_encode`
- `keys`
- `array_keys`
- `strtolower`
- `lower`
- `merge`
- `nl2br`
- `number_format`
- `raw`
- `replace`
- `reverse`
- `strrev`
- `round`
- `slice`
- `sort`
- `split`
- `explode`
- `striptags`
- `title`
- `trim`
- `upper`
- `strtoupper`
- `url_encode`

!!! warning
	Filter functions are a beta feature and may not work properly in some circumstances.
