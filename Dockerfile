# Client site — Laravel + Vue (Inertia) served by FrankenPHP for production.
# Assets are pre-built and committed, so the image build is just composer (fast);
# no npm at build time. FrankenPHP serves public/ over plain HTTP on :8080 and
# isolates each request, so a single PHP error returns a 500 without ever
# taking the server down. TLS is terminated by the upstream proxy.
FROM dunglas/frankenphp:1-php8.4

# System deps + PHP extensions. PostgreSQL (pdo_pgsql) AND SQLite (pdo_sqlite)
# are both present so the same image works against either database.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libsqlite3-dev \
    && install-php-extensions \
        pdo_pgsql \
        pgsql \
        pdo_sqlite \
        zip \
        bcmath \
        intl \
        opcache \
        pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Use the tuned production php.ini shipped with the image.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# Install prod dependencies, discover packages, and prepare writable dirs + the
# default SQLite database file at build time.
RUN cp .env.production .env \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p database \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R ug+rwX storage bootstrap/cache database \
    && chmod +x docker/start.sh

# FrankenPHP server config (HTTP :8080, classic PHP mode).
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 8080

CMD ["sh", "docker/start.sh"]
