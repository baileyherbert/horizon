# Localization

## Introduction

Horizon has implemented its own format for localization files, called Simplified Internationalization Templates. This document will outline how
to write these files, and how they can be used.

### File locations

You can hard-code localization files into the `app/translations` directory or load them through a service provider.

## Syntax

### Headers

You can add metadata to a translation file by using headers. These aren't useful for Horizon itself, but may be useful
to you with custom functionality or extensions. They can also simply help document your files.

Here are some example headers. Each go on their own line.

```sit
@name English
@version 1.2.3
@author The Horizon Project
```

### Variables

Localization files can also have variables. Again, not particularly useful for Horizon, but potentially useful for
custom functionality that you implement.

```sit
@var "StringVariableName" "StringValue"
@var "BoolVariableName" true
@var "NumberVariableName" 5200.0
```

### Comments

Of course, you can't have a syntax without support for comments.

```sit
# This is a comment
```

### Namespaces

Namespaces are groups of definitions. Ideally, you will want a namespace to represent either a single page, or a smaller
part of a page.

```sit
@namespace horizon.index
```

### Definitions

After declaring a namespace, you can write as many definitions as you want. Original text goes on the left side, and
the translated text goes on the right.

```sit
@namespace horizon.index

"Original text..."    "My original text!"
"More text..."        "More of my text!"
```

### Variables

You can also put variables in definitions. They will use the context of the view when they're outputted.

```sit
"This has a {{ variable }}."        "The {{ variable }} is working."
"This has a {{ nested.variable }}." "The {{ nested.variable }} is working."
```

## Automatic translation

Text in your views can be automatically translated using namespaces.

### Usage

To enable automatic translations, use the `@translate` method at the top of your view file. You can call it multiple times
to enable translation using multiple namespaces.

```html title="View"
@translate('horizon.index')
@translate('horizon.some_other.namespace')

<div class="container">
    <strong>This is the body</strong>
    <strong>Hello, {{ username }}.</strong>
</div>
```

Then, the system will see any matching text within the template, using appropriate boundaries.

```sit title="Translation"
@namespace horizon.index

"This is the body"      "Welcome to my website!"
"Hello, {{username}}"   "You're logged in, {{ username }}."
```

```html title="Result"
<div class="container">
    <strong>Welcome to my website!</strong>
    <strong>You're logged in, {{ username }}.</strong>
</div>
```

## Flags

Each definition can have flags which change its functionality during auto-translation.

|Flag|Description|
|---|---|
|`i`|Enables case-insensitive matching.|
|`x`|Ignores extra whitespace.|
|`r`|Turns the left quote into a regular expression.|

Flags go after a slash (`/`) at the end of the line. Here are example functionalities of each.

```sit
"First text"        "It's case-insensitive"          /i
"Second text"       "It ignores extra whitespace"    /x
"Third text"        "It's both of the above"         /ix
"/Fourth\s+text/i"  "It's a regular expression"      /r
```

This will result in the following translations.

```
FIRST text!         ->    It's case-insensitive!
Second   text!      ->    It ignores extra whitespace!
third  TEXT!        ->    It's both of the above!
fourth    text!     ->    It's a regular expression!
```

## Other notes

Here are some other things to know when using auto translate.

- It doesn't care about spaces inside `{{ variables }}` when matching text.
- If auto-translation gets too slow, consider breaking translations down into a larger number of namespaces.
- You can also use the [`@__()`](templates.md#translating_strings) method to translate text much faster.
