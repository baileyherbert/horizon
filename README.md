<p align="center">
    <img alt="Illustration of sun behind mountains" src="https://i.bailey.sh/JjnmDLX.png" width="196" />
</p>

<p align="center">
  <a href="https://github.com/baileyherbert/horizon" target="_blank" rel="noopener noreferrer">github</a> &nbsp;/&nbsp;
  <a href="https://packagist.org/packages/baileyherbert/horizon" target="_blank" rel="noopener noreferrer">packagist</a> &nbsp;/&nbsp;
  <a href="https://docs.bailey.sh/horizon/latest/" target="_blank" rel="noopener noreferrer">documentation</a>
</p>

<h1 align="center">Horizon Framework</h1>

## Introduction

Horizon is a PHP web application framework largely inspired by Laravel 5. It was designed from the ground up to work on
nearly all web servers, while offering most of the same features and a familiar development experience.

Horizon is a great fit for distributed web applications, and works wonderfully on shared hosting. With no strict extension
requirements, support for older PHP versions, dynamic subdirectory support, and automatic legacy routing, it can run pretty much
anywhere.

## Quick start

There are two starter templates in this repository to get up and running quickly. Though they share the same core framework, each template has been configured and structured differently to meet user requirements.

- **[Dedicated](https://github.com/baileyherbert/horizon/tree/master/starters/dedicated)** – A template for private projects where you control the environment or run in Docker.
- **[Shared](https://github.com/baileyherbert/horizon/tree/master/starters/shared)** – A template for distributed projects where you don't control the environment.

## Features

Horizon is a full-fledged framework built to run where other frameworks won't.

- **Database ORM** (with query building, models, and relationships)
- **Database migrations** (with an elegant schema builder)
- **Routing** (with fallback routing and plenty of configurability)
- **Views** (with a blade-like twig syntax, and reusable components)
- **Translations** (with optional automatic view translation)
- **Console** (with the [`ace`](https://packagist.org/packages/baileyherbert/ace) tool, custom commands, and invocation from code)

## Server requirements

### Hard requirements

Horizon was built to work just about anywhere. A critical part of this goal was to minimize its server requirements. There are just a couple hard requirements:

- Horizon **requires** running on a webserver with PHP 5.6 or above.
- Horizon **requires** one of the following extensions when using databases.
    1. `pdo_mysql`
    2. `mysqli`
    3. `mysqlnd`
    4. `mysql`

### Recommendations

Horizon will use polyfills or fallback implementations when certain useful extensions are missing. For the best possible runtime experience, the following additional features are strongly recommended:

- Horizon **recommends** running on a webserver that supports `.htaccess` files.
- Horizon **recommends** running on a webserver that supports rewrite rules.
- Horizon **recommends** the `mbstring` and `openssl` extensions.

### Shared hosting

Horizon's [shared template](https://github.com/baileyherbert/horizon/tree/master/starters/shared) was designed to automatically work on shared hosting without any additional configuration or effort from users.

- Horizon can run in a subdirectory. Links, redirects, and asset paths will be adjusted automatically.
- Horizon can run without rewrite rules. It will fall back to using `.php` route files instead.

## License

[MIT](https://github.com/baileyherbert/horizon/blob/master/LICENSE.md)
