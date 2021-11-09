# Horizon Framework

This is a starter template for the Horizon Framework that is designed for building applications which will be
distributed to end users.

The `vendor` directory is hidden in a subfolder which can be renamed. Additionally, multiple `.htaccess` files have
been placed throughout the project to control access and routing. When routing is unavailable, legacy routing will
automatically kick in.

## Installation

Run the following commands to clone this template and install dependencies into the current working directory.

```bash
npx degit baileyherbert/horizon/starters/shared
composer install -d horizon
```

If you haven't done so already, consider installing the `ace` command line tool globally as well.

```bash
composer global require baileyherbert/ace
```

## Deployment

Package the entire application into an archive and distribute it. Instruct end users to extract the contents of the
archive into the directory on their server where they would like the application to appear. Then, have them create the
`.env.php` file using the included template and customize it.

## More links

Check out the following links for more information.

- Documentation: https://bailey.sh/packages/horizon/docs/
- Repository: https://github.com/baileyherbert/horizon
