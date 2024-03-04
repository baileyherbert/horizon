# File structure

## Application

The `app` directory stores all application files, such as libraries, packages, source code, views, and extensions. It
is the primary workstation of any Horizon application.

|Directory|Description|
|---|---|
|`/config`|Project configuration files|
|`/errors`|Custom HTTP error pages|
|`/extensions`|Themes, plugins, etc|
|`/public`|Public assets like images and styles|
|`/translations`|Translation files|
|`/routes`|Route declaration files|
|`/src`|Project source code and libraries|
|`/views`|Hardcoded view templates|
|`/vendor`|Optional extra composer packages|

Most of these paths can be customized in the `app:paths` configuration option.

## Horizon

The `horizon` directory is where internal framework libraries are stored. It also hosts a composer vendor directory,
a testing suite, default error pages, and other vital resources.

!!! danger
	Don't edit or directly depend on files in the `horizon` directory, as these files are strictly internal and are
	subject to change in future releases.
