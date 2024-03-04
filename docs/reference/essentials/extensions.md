# Extensions

## Introduction

Horizon implements a barebone extensions system which can be extended and built upon to create systems like themes and
plugins for your application. The default implementation allows extensions to map their own namespace and source files,
require a vendor autoloader, and register their own service providers.

This means that in the default implementation and without any additional work needed, extensions can already add views,
translations, routes, and updates.

## Creating extensions

The default directory for extensions is `/app/extensions/`. In this directory, you should create a new subdirectory
with a simplified name of the extension. For this guide we'll create an extension with the boring name of `custom`, so
we should create a directory at `/app/extensions/custom/`.

### Configuration file

Once we have our empty extension directory, create a new PHP file with the same name as the folder. In this case, we'll
name it `custom.php`. The file should return an array with at least a `name` and `version`. Here are all possible
properties:

```php title="app/extensions/custom/custom.php"
<?php

return array(
    // Basic properties
    'name' => 'Test',
    'description' => 'This is a test extension that does something cool.',
    'version' => '1.0.0',

    // Optional main class
    'main' => 'Custom\TestExtension',

    // Optional namespace mapping (for autoloading)
    'namespaces' => array(
        'Custom\\' => 'src/'
    ),

    // Optional files to require
    'files' => array(
        'vendor/autoload.php'
    ),

    // Optional service providers to register
    'providers' => array(
        'Custom\\CustomServiceProvider'
    )
);
```

## Retrieving extensions

You can retrieve an array of all extensions registered in the application using the extension kernel. The extensions
are all `Horizon\Extension\Extension` instances.

```php
$extensions = Application::kernel()->extension()->get();
```

### Retrieving errors

If an extension fails to load due to an exception, there will be no visible output regarding the error. It will fail
silently. However, in some cases it is ideal to be able to see these exceptions and display them to the user. You can
retrieve an array of `Horizon\Extension\Exception` instances from the extension kernel.

```php
$exceptions = Application::kernel()->extension()->getExceptions();

foreach ($exceptions as $exception) {
    $name = $exception->getName();
    $message = $exception->getMessage();

    echo "Extension {$name} failed due to an error: {$message}";
}
```

## Techniques

### Implementing themes

While a theme system is not built into the framework, it is possible to utilize extensions to build one quite easily.
The steps will look something like this:

- Set up a `ThemeExtension` class which extends `Extension`.
  - Override and implement `isEnabled()`.
  - Add a default view and translation service provider.
- Set up a service provider which loads from `/app/themes/`.
  - The provider should say it provides `Extension` instances.
  - It should actually provide `ThemeExtension` instances.
- Add a boot script with a priority of `1` or `2`.
  - From this script, find extensions that are instances of `ThemeExtension`.
  - Register those extensions in your own theme manager.
