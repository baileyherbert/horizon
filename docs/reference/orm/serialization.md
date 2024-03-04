# Serialization

## Introduction

At the moment, Horizon supports basic serialization. This allows you to easily output a model in JSON or retrieve an array,
which is useful particularly to APIs.

## Serialize to JSON

Use the instance's `toJson()` method to obtain a pretty-formatted JSON string.

```php
$json = $user->toJson();
```

## Serialize to array

Use the instance's `toArray()` method to obtain an associative array.

```php
$data = $user->toArray();
```
