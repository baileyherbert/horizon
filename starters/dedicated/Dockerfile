FROM php:8.0-apache

RUN docker-php-ext-install mysqli
RUN docker-php-ext-install opcache

RUN a2enmod rewrite

ADD . /var/www
ADD ./public /var/www/html

# Allow the cache directory to be written
# If you are confident that you'll call "ace build" before each deployment then remove this for better security
RUN mkdir -p /var/www/app/cache
RUN chmod a+rwx -R /var/www/app/cache

ADD app/.docker/httpd.conf /etc/apache2/apache2.conf
ADD app/.docker/host.conf /etc/apache2/sites-available/000-default.conf
COPY app/.docker/php.ini /usr/local/etc/php/
