# ------------------------------------------------------------------
# Core Platform — Backend Dockerfile
#
# PHP-FPM container for Laravel application.
#
# Runtime ownership strategy:
#   PHP-FPM pool runs as www-data (uid=33). Application files are
#   volume-mounted from the host (owner uid=1000). The entrypoint
#   script fixes storage/ and bootstrap/cache/ ownership at startup
#   so www-data can write compiled views, cache, and logs.
#   See infra/docker/backend/entrypoint.sh for details.
# ------------------------------------------------------------------

FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install intl pdo pdo_pgsql zip

# /tmp is used by PHP's tempnam() as a last resort. Ensure it is
# world-writable with sticky bit so every process can create temp
# files without collisions.
RUN mkdir -p /tmp && chmod 1777 /tmp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Entrypoint fixes runtime directory ownership before PHP-FPM starts.
# This ensures www-data can write regardless of how files arrived
# (host mount, docker cp, artisan run as root, etc.).
COPY infra/docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]