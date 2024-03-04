# Autoloader

## Introduction

Horizon's autoloading is more complicated than other frameworks due to its support for shared hosting, extensions, and
remote updates. It has its own global autoloader, and can have multiple separate vendor directories.

## Composer

### Application vendors

Applications that use composer packages should have their own `app/composer.json` file. The framework automatically
requires the `app/vendor/autoload.php` file if it exists.

!!! warning
	Horizon has its own composer file at `horizon/composer.json`. However, this is an internal file that should never
	be modified. To add additional dependencies, you must initialize your own file at `app/composer.json`.

### Extension vendors

Extensions should have their own composer installations and vendor directories. Note that these vendor autoloaders are
_not_ automatically required. Instead, extensions can import the autoloader file via the `files` configuration option.
For example:

```php
'files' => array(
    'vendor/autoload.php'
)
```

For more information on extensions and their configuration, refer to the
[extensions documentation](../essentials/extensions.md).

## Namespaces

The `config/namespaces.php` file specifies namespaces which will be "mounted" during startup. This file can be used to
easily create new namespaces pointing to different source directories. The `App` namespace is registered through this
file by default as well.

```php title="config/namespaces.php"
return array(
    'map' => array(
        'App\\' => '/app/src'
    )
);
```

### Manual mounting

To mount a namespace programatically, you can use the static `Autoloader` service. This is a foundation facade and its
interface will not change in minor updates. You can mount namespaces at any point in the application's execution – the
mapping will take effect immediately.

```php
use Horizon\Foundation\Services\Autoloader;

Autoloader::mount('App\\', '/absolute/path/to/src');
```
