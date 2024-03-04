# Components

## Introduction

A component is a template with a PHP class attached to it. Each time the component is called, a new instance of
that class is created. The constructor of the class can have parameters, and the component's template file can access
properties and methods of the class using `$this`.

This is a great system for implementing advanced parts of pages, such as navigation menus, which may require the
assembly of menu items, calculation of the current page, etc., and might otherwise be difficult to position in a
larger application.

## Registering a component

### The components directory

The directory to place default components is under `/app/components/`. For example, to create a default component named
`menu`, we would create `/app/components/menu.twig`. All files under this directory are automatically registered.

### Providing and overriding components

You can manually register a component using the `Component` facade from the boot method of a
[service provider](../architecture/service-providers.md). Registering a component this way will always override any components
in the default directory.

```php
use Horizon\Support\Facades\Component;
use Horizon\Support\Services\ServiceProvider;

public class CustomServiceProvider extends ServiceProvider {

	public function boot() {
        Component::register('name', '/absolute/path/to/component.twig');
    }

}
```

## Designing a component

Component template files have access to all of the same functions and syntax as normal view templates. However, you
must take care to write them in the proper format.

### Writing the component class

Create a class which extends `Horizon\View\Component`. The constructor can accept any arguments.

```php
namespace App\View\Components;

use Horizon\View\Component;

class MenuComponent extends Component {

    private $menuName;

    public function __construct($name) {
        $this->menuName = $name;
    }

    public function getName() {
        return $this->menuName;
    }

}
```

### Writing the template file

Before a component can be used, it must specify the class that it depends on by calling the `@using()` function at the
very top of the template.

```html
@using('App\View\Components\MenuComponent')

<nav>
    This is the {{ $this->getName() }} menu component.
</nav>
```

The specified class name will be loaded in two ways. First, the app's service container will be checked to see if an
instance is provided. If no instance is provided, it will construct an instance of the specified class directly. This
allows extensions to override the component's functionality without touching the template.

## Calling a component

With our example above, the constructor accepts a `$name` argument. With that in mind, we can invoke the component
in two different ways. Both of the examples below result in the following output from the component:

```html
<nav>
    This is the primary menu component.
</nav>
```

### Call from a view

Use the `@component` function from within a view to create and render the component at that location. Pass constructor
arguments after the first argument (which is the component name).

```html
<div class="container">
    @component('menu', 'primary')
</div>
```

### Call using the facade

```php
use Horizon\Support\Facades\Component;

$html = Component::compile('menu', 'primary');
```

## Advanced

### Context variables

From within a component class, you have access to the context variables of the template which called the component. For
example:

```php title="Controller"
return view('index', [
	'color' => 'red',
	'make' => 'ford'
]);
```

```html title="View (index.twig)"
@component('example', $color)
```

```php title="Component"
class ExampleComponent extends Component {

    public function __construct($color) {
        $variables = $this->getContext();

        echo $color; // red

        echo $variables['color']; // red
        echo $variables['make']; // ford
    }

}
```
