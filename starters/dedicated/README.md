# Horizon Framework

This is a starter template for the Horizon Framework that is designed for building private applications which will not
be distributed. It comes with all the files needed to host it with Docker and has deployment workflows ready to go.

## Installation

Run the following commands to clone this template and install dependencies into the current working directory.

```bash
npx degit baileyherbert/horizon/starters/dedicated
composer install
```

If you haven't done so already, consider installing the `ace` command line tool globally as well.

```bash
composer global require baileyherbert/ace
```

## Deployment

For manual deployment, run the `build` command to precompile views and reduce runtime directory scans.

```php
ace build
```

Then upload the application to the server of choice. Please ensure that you have set your environment variables and
that your virtual host is configured to serve from the `public` directory.

## More links

Check out the following links for more information.

- Documentation: https://docs.bailey.sh/horizon/latest/
- Repository: https://github.com/baileyherbert/horizon
