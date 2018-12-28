# Horizon

This is my personal framework for writing web applications. The goal with this framework was to support as many server
configurations as possible, and reduce requirements to a minimum. I'd like to say I succeeded.

## Requirements

The actual requirements are pretty simple:

- PHP 5.4 or higher.
- One supported database extension (currently `mysql`, `mysqli`, and `pdo_mysql`.

The following are **optional** but recommended:

- Rewrite rules. The framework's routing system can fall back to using .php files though.
- Mbstring. But if you don't have this you're probably crazy.

## Documentation

The documentation is hosted in a [separate repository](https://github.com/baileyherbert/horizon-docs). Here are some quick links.

**Get started**
- [Introduction](https://github.com/baileyherbert/horizon-docs/blob/master/index.md)
- [Requirements](https://github.com/baileyherbert/horizon-docs/blob/master/requirements.md)
- [Installation](https://github.com/baileyherbert/horizon-docs/blob/master/installation.md)

**Architecture**
- [File Structure](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/files.md)
- [Service Container](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/container.md)
- [Service Providers](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/providers.md)
- [Facades](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/facades.md)

**Essentials**
- [Routing](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/routing.md)
- [Middleware](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/middleware.md)
- [Controllers](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/controllers.md)
- [Requests](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/requests.md)
- [Responses](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/responses.md)
- [Views](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/views.md)
- [Sessions](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/sessions.md)
- [Errors](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/errors.md)
- [Extensions](https://github.com/baileyherbert/horizon-docs/blob/master/essentials/extensions.md)

**Frontend**
- [Templates](https://github.com/baileyherbert/horizon-docs/blob/master/frontend/templates.md)
- [Components](https://github.com/baileyherbert/horizon-docs/blob/master/frontend/components.md)
- [Localization](https://github.com/baileyherbert/horizon-docs/blob/master/frontend/localization.md)

**Database**
- [Query Builder](https://github.com/baileyherbert/horizon-docs/blob/master/database/query_builder.md)
- [Migrations](https://github.com/baileyherbert/horizon-docs/blob/master/database/migrations.md)

**ORM**
- [Models](https://github.com/baileyherbert/horizon-docs/blob/master/orm/models.md)
- [Relationships](https://github.com/baileyherbert/horizon-docs/blob/master/orm/relationships.md)
- [Serialization](https://github.com/baileyherbert/horizon-docs/blob/master/orm/serialization.md)

**Updates**
- [Repositories](https://github.com/baileyherbert/horizon-docs/blob/master/updates/repositories.md)
- [Scripting](https://github.com/baileyherbert/horizon-docs/blob/master/updates/scripting.md)
- [Client](https://github.com/baileyherbert/horizon-docs/blob/master/updates/client.md)
