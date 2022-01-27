FROM php:8.0-apache

# Server setup
RUN a2enmod rewrite
RUN a2enmod remoteip
RUN apt-get update && apt-get install git zip unzip libzip-dev -y

# Add required PHP extensions
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install opcache
RUN docker-php-ext-install zip
RUN docker-php-ext-install calendar

# Target directory is /var/www for base files
# Target public directory is /var/www/public
WORKDIR /var/www

# Copy server files
ADD app/.docker/httpd.conf /etc/apache2/apache2.conf
ADD app/.docker/host.conf /etc/apache2/sites-available/000-default.conf
COPY app/.docker/php.ini /usr/local/etc/php/

# Install dependencies
COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN composer install

# Copy files
ADD . ./

# Build for production
RUN php vendor/bin/ace build
RUN chmod -R 0555 app/cache
