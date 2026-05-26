# ------------------------------------------------------------------
# Core Platform — Backend Dockerfile
#
# PHP-FPM container for Laravel / Filament application.
#
# Runtime ownership strategy:
#   PHP-FPM pool runs as www-data (uid=33). Application files are
#   volume-mounted from the host (owner uid=1000). The entrypoint
#   script fixes storage/ and bootstrap/cache/ ownership at startup
#   so www-data can write compiled views, cache, and logs.
#   See infra/docker/backend/entrypoint.sh for details.
# ------------------------------------------------------------------

FROM php:8.3-fpm

# ── System dependencies ───────────────────────────────────────────
# libgd-dev   → gd (image processing, required by Filament)
# libpq-dev   → pdo_pgsql, pgsql
# libzip-dev  → zip
# libicu-dev  → intl (locale / money formatting)
# netcat-openbsd → nc, used in the container HEALTHCHECK
RUN apt-get update && apt-get install -y --no-install-recommends \
    libgd-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    netcat-openbsd \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions (compiled) ─────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo \
        pdo_pgsql \
        pgsql \
        zip

# ── PHP extensions (PECL) ─────────────────────────────────────────
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# ── PHP runtime configuration ─────────────────────────────────────
COPY infra/docker/backend/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY infra/docker/backend/php/app.ini     /usr/local/etc/php/conf.d/99-app.ini

# /tmp needs sticky bit so every process can create temp files.
RUN chmod 1777 /tmp

# ── Composer ──────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Entrypoint ────────────────────────────────────────────────────
COPY infra/docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ── Healthcheck ───────────────────────────────────────────────────
# Verify php-fpm is accepting FastCGI connections on port 9000.
HEALTHCHECK --interval=10s --timeout=5s --retries=5 --start-period=30s \
    CMD nc -z 127.0.0.1 9000 || exit 1

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]