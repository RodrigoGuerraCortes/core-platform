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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/ .

RUN composer install --no-interaction --prefer-dist

RUN php artisan storage:link || true

EXPOSE 9000

CMD ["php-fpm"]
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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/ .

RUN composer install --no-interaction --prefer-dist

RUN php artisan storage:link || true

EXPOSE 9000

CMD ["php-fpm"]
