# Facades

## Introduction

Horizon offers a set of static helper classes which provide shortcuts for common tasks, called facades. We recommend
using these facades wherever possible to reduce code complexity.

## Available facades

### Ace

The `Horizon\Support\Facades\Ace` facade allows invoking `ace` commands directly from your code. Rather than spawning a
child process, this will directly invoke the framework's console libraries and emulate a console environment.

### Application

The `Horizon\Foundation\Application` facade provides a large number of helper functions and utilities, including for
service container resolution, path resolution, and configuration retrieval.

### Component

The `Horizon\Support\Facades\Component` facade provides methods to register or render HTML
[view components](../frontend/components.md).

### Framework

The `Horizon\Foundation\Framework` facade provides some functions for internal purposes (such as for resolving paths
within the internal `horizon` directory). Notably, it offers a function to retrieve the framework version.

### Http

The `Horizon\Support\Facades\Http` facade provides methods to retrieve common objects from the HTTP kernel, such as the
request and response objects. It also provides a shortcut to reference the HTTP kernel directly.
