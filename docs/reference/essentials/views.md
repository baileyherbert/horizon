# Views

## Introduction

Horizon's views are built on top of Twig, and we have implemented an additional layer of parsing to help implement an
optional Blade-like syntax, automatic localization, and a unique take on components.

## Registering views

### Default directory

The default view service provider registers a loader which automatically looks for files in the `/app/views/` directory.
Placing your view files in this location is the quickest way to get going and requires no configuration.

### Custom service provider

The default view provider is at `Horizon\View\ViewServiceProvider` which provides extensions and the primary view loader.
You can write your own provider to register a new directory by providing a `Horizon\View\ViewLoader` instance.

```php
namespace App\View;

use Horizon\Framework\Application;
use Horizon\Support\Services\ServiceProvider;

class CustomViewServiceProvider extends ServiceProvider {

    private $viewDirectory = '/app/views';

    public function register() {
        $this->bind('Horizon\View\ViewLoader', function() {
            return new ViewLoader(Application::path($this->viewDirectory));
        });
    }

    public function provides() {
        return array(
            'Horizon\View\ViewLoader'
        );
    }

}
```

!!! note
	There can be multiple view loaders at once. If two or more loaders are capable of providing a view file for a
	requested path, the last one will be used.

## Extensions

You can use a view extension to build on top of templates, add your own functions, or implement new syntax. View
extensions consist of three parts:

1. **Transpiler tags** – Defined tags preceded by a `@` character which get converted into Twig syntax. For example,
   the `@if` statement, or `@csrf` function are "transpiled" into Twig syntax.
2. **Globals** – Variables that are accessible in the environment of every view file, such as `$request` and `$route`.
3. **Functions** – Custom functions that can be called in Twig blocks, like `{{ session() }}`.

### Defining an extension

The default extension loader looks for all files in `App\View\Extensions`, so you can immediately register an extension
by creating it in that namespace. It should extend `Horizon\View\ViewExtension`.

```php
namespace App\View\Extensions;

use Horizon\View\ViewExtension;
use Twig_SimpleFunction;

class CustomExtension extends ViewExtension {

    protected function twigKernelTime() {
        return new Twig_SimpleFunction('kernel_time', function () {
            return Profiler::time('kernel');
        });
    }

}
```

In the example above, we instantly add a new function `kernel_time()` to the Twig environment by declaring a new
method starting with `twig`.
