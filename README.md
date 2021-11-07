# Horizon

This is a web application framework that was designed from the ground up to work on virtually all servers.

## Inspiration

Laravel is a great framework, but it isn't suitable for building distributed web applications because:

- It requires rewrite rules.
- It requires the latest PHP versions.
- It requires several extensions including PDO.
- It won't work in subdirectories.

Horizon has none of these constraints. It will automatically choose from the database extensions installed on the
server. It will switch to legacy routing with `.php` files on servers that don't support rewrite rules. It works as
far back as PHP 5.4. All of that, while still offering many of the same features, albeit with less depth.

Horizon is intended to be used for developing applications that will be sold on [CodeCanyon](https://codecanyon.net).

## Documentation

This framework is undergoing some heavy reworking and documentation is out of date.

**Get started**
- [Introduction](https://github.com/baileyherbert/horizon-docs/blob/master/index.md)
- [Requirements](https://github.com/baileyherbert/horizon-docs/blob/master/requirements.md)
- [Installation](https://github.com/baileyherbert/horizon-docs/blob/master/installation.md)

**Architecture**
- [File Structure](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/files.md)
- [Service Container](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/container.md)
- [Service Providers](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/providers.md)
- [Environment](https://github.com/baileyherbert/horizon-docs/blob/master/architecture/environment.md)
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
