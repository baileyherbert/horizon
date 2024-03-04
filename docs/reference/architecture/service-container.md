# Service container

## Introduction

The service container is a registry that can be used to retrieve an instance of a class which implements a desired
feature, without hardcoding the class name.

To accomplish this, the container uses "service providers," each of which give the container a list of classes and
identify the features that those classes implement.

You can retrieve the application's global service container using the `app()` global helper function. You can also
create your own containers for smaller use cases.

## Registering providers

The framework automatically registers providers that are listed in the `config/providers.php` file. Extensions can also
configure a list of their own providers to register.

To register a provider manually, the `register()` method is used.

```php
app()->register(new CustomServiceProvider());
```

!!! warning
	Providers have a `boot()` method which can be used to run bootstrapping logic when the framework starts up. This
	is normally invoked by the framework when they are registered via configuration files. However, when registering a
	provider manually like above, the `boot()` method will not be called automatically.

## Resolving instances

### Resolving singletons

The service container allows you to resolve a single class instance from a particular type, called a singleton. This
is the most common method of resolution. If multiple providers are offering instances for the requested type, only the
last-registered instance will be resolved.

You can use the `make()` method on a container to make a single instance of a target type.

```php
$instance = app()->make('App\Dependency');
```

If you're only using the global service container, then the `resolve()` helper function is available as a convenient
shortcut.

```php
$instance = resolve('App\Dependency');
```

### Resolving collections

In some cases, you'll want to retrieve all instances of a target type, from all of the providers that have registered it
across the application.

The application uses this strategy to load a number of core components, including extensions, views, and translations.
It's a great way to load dynamic objects that might come from the user.

You can use the `all()` method on a container to retrieve a `ServiceObjectCollection`, which holds a list of all
resolved objects.

```php
$collection = app()->all('App\Dependency');

$instances = $collection->all(); // Get an array of instances
$first = $collection->first(); // Get the first instance

while ($instance = $collection->next()) // Iterate using next()
foreach ($collection as $instance) // Iterate using foreach()
```
