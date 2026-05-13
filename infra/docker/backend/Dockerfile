# ------------------------------------------------------------------
# Core Platform — Backend Dockerfile
#
# PHP-FPM container for Laravel application.
# ------------------------------------------------------------------

FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

RUN mkdir -p /tmp && chmod 777 /tmp

COPY --from=composer:latest  /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]