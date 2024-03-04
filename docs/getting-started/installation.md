# Installation

## Installing from a template

Horizon's repository offers two different starter templates suitable for different types of projects. The recommended
means of initializing a new project is to use one of these templates.

### Shared template

The [shared starter](https://github.com/baileyherbert/horizon/tree/master/starters/shared) is best for distributed
applications that need to run in unpredictable environments. This starter works in subdirectories and does not need
rewrite rules.

```
npx degit baileyherbert/horizon/starters/shared
composer install -d horizon
```

### Dedicated template

The [dedicated starter](https://github.com/baileyherbert/horizon/tree/master/starters/dedicated) is best for private
projects where the developer has full control of the environment or will be deploying with Docker. Traffic must be
pointed to the `public` directory, and rewrite rules should always be enabled.

```
npx degit baileyherbert/horizon/starters/dedicated
composer install
```

!!! note
	Horizon's documentation is largely based on the shared template, which uses a different file structure than the
	dedicated template. For example, while the shared template can have two different vendor directories, the dedicated
	template only has one.

## Installing from source

The latest release can be fetched from the repository on [GitHub](https://github.com/baileyherbert/horizon/releases).
Download these files and extract them into your project's public directory. Next, open a terminal in the same directory,
and run this command:

```
composer install -d horizon
```

## Installing the `ace` tool

Installing the `ace` command line tool globally is recommended to aid with development. This tool helps with generating
new source files, building the app, running your own custom commands, and more.

```
composer global require baileyherbert/ace
```
