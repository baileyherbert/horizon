# Requirements

## PHP

Horizon supports PHP 5.6 and above. Older versions of PHP may still work but are not officially supported and should
not be used.

## Extensions

Horizon doesn't have any specific PHP extension requirements. However, the following extensions are highly recommended
for improved performance:

- `mbstring`
- `openssl`

For applications that use a MySQL database, the framework will automatically use the best of any available extensions,
which includes:

- `mysql`
- `mysqli`
- `pdo_mysql`

## Webserver

Horizon will run on any webserver as long as it meets the other requirements on this document. No special server
configuration is required to use the framework.

If `.htaccess` files are not supported in the environment, Horizon will automatically fall back to using legacy `.php`
file-based routing. This is automatically applied and managed by the framework when links, routing, and building are
all implemented correctly.

## Filesystem

Horizon can be installed into any public-facing directory on a supported server, including in subdirectories. The
framework will detect that it's running in a subdirectory, and will adjust routing, links, and redirects accordingly.
