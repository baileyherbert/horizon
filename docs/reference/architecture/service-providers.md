# Service providers

## Introduction

Horizon implements a basic service provider which is used by the [service container](service-container.md) to instantiate
objects for services that need them. Internally, they are used for views, routes, components, translations, and so on.
It is also possible to utilize providers for your own applications as well, so having an understanding of this concept
is fairly important.

You can see all of the registered providers in the `config/providers.php` file. By default, the core essentials are in
this file, but you can remove them and write your own to change how the framework functions.

## Writing service providers

You can create a class anywhere in your source code and extend the core service provider class to get started. The
following code sample shows the basic methods you'll be implementing.

```php title="src/Providers/CustomProvider.php"
namespace App\Providers;

class CustomProvider extends Horizon\Support\Services\ServiceProvider {

    /**
     * Boots the service provider.
     */
    public function boot() {}

    /**
     * Gets a list of all class names that the service provider can provide.
     *
     * @return string[]
     */
    public function provides() {}

    /**
     * Registers the bindings in the service provider.
     */
    public function register() {}

}
```

### Booting the provider

The `boot()` method is called by the framework's kernel as soon as the service provider is loaded. By default, this
happens immediately after autoloader namespaces are fully loaded, so quite early in the app lifecycle.

!!! warning
	Avoid placing bootstrap logic in the constructor.

### Describing types

The `provides()` method is expected to return an array of types that the provider can make. This will generally be an
array of fully qualified class names.

```php
public function provides() {
    return array(
        'App\Vehicles\Car',
        'App\Vehicles\Bike'
    );
}
```

### Resolving instances

The `register()` method allows binding a provided class name to a callable which will return an instance of that class.
To do this we use the protected function `bind()`. If for some reason you are unable to provide an instance from within
the callable, you can return `null`.

```php
public function register() {
    // We can return a single instance
    $this->bind('App\Vehicles\Car', function() {
        return new Car('2018 Honda Civic', 'Blue');
    });

    // We can also return an array of instances
    $this->bind('App\Vehicles\Bike', function() {
        return array(
            new Bike('Kawasaki Ninja 300', 'Green'),
            new Bike('Kawasaki Ninja 300', 'Red')
        );
    });
}
```

Note that the declaration of types (via the `provides()` method) is done separately from this method in order to support
deferred providers, which will be touched on further below.

## Registering providers

Most providers are registered in the `config/providers.php` file. To register a new provider, you only need to add its
class name to the array.

```php title="config/providers.php"
return array(
    'Horizon\Routing\RoutingServiceProvider',
    'Horizon\View\ViewServiceProvider',
    'Horizon\Extension\ExtensionServiceProvider',
    'Horizon\Translation\TranslationServiceProvider',
    'Horizon\Updates\UpdateServiceProvider',
    'App\Providers\CustomProvider'
);
```

!!! note
	It's possible to use the `::class` resolver instead of strings. However, because this is a PHP 5.6 feature, strings
	are used by default to keep the framework compatible with older versions.

## Deferred providers

For providers that are loaded from the configuration file, the `boot()` method will be invoked automatically by the
framework. However, it's possible to mark a provider as **deferred**.

When a provider is deferred, the container still calls the `provides()` method to check what types it can construct.
However, the `boot()` and `register()` methods won't be called until one of those types is actually requested at
runtime.

This is generally not necessary for simple or lightweight providers. However, for providers with heavy bootstrapping
logic, this can be an invaluable tool to improve performance.

To defer a provider, simply set the protected member `$defer` to `true`.

```php
class CustomProvider extends ServiceProvider {

    protected $defer = true;

}
```
