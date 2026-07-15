# syntax=docker/dockerfile:1

# ---- Stage 1: build front-end assets (Vue/Inertia via Vite) ----
FROM node:22-alpine AS assets
WORKDIR /app

# Install JS deps against the committed lockfile for reproducible builds
COPY package.json package-lock.json ./
RUN npm ci

# Sources needed by `vite build` (Laravel plugin reads vite.config.js + resources)
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# Emits public/build/manifest.json + hashed assets
RUN npm run build


# ---- Stage 2: PHP runtime (FrankenPHP) ----
# Pin to a specific patch tag in production, e.g. dunglas/frankenphp:1.9-php8.2
FROM dunglas/frankenphp:1-php8.2 AS runtime
WORKDIR /app

# System libraries + PHP extensions.
#   pdo_pgsql -> Render PostgreSQL (the whole point)
#   mbstring, zip, exif, pcntl, bcmath, gd -> requested / Laravel + queue signal handling
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libonig-dev \
        libzip-dev \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libcap2-bin \
        supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql mbstring zip exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Render (and many container runtimes) enforce no-new-privileges. Linux refuses
# to execve() a binary carrying file capabilities under that flag, returning
# EPERM. FrankenPHP ships with cap_net_bind_service set; strip it since we bind
# the high $PORT as root and don't need it.
RUN setcap -r "$(command -v frankenphp)" || true

# Composer binary from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies first for better layer caching.
# --no-scripts / --no-autoloader: app code isn't present yet, so defer package
# discovery + autoloader generation until after the full source is copied.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# Application source, then overlay the freshly built front-end assets
COPY . .
COPY --from=assets /app/public/build ./public/build

# Generate optimized autoloader and run package discovery now that app code exists
RUN composer dump-autoload --optimize --no-dev

# Writable runtime dirs. Container runs as a single UID (root) so there is no
# cross-user cache-write conflict between start.sh and the supervisord children.
RUN chmod -R 775 storage bootstrap/cache

# Runtime configuration
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Render injects $PORT at runtime; FrankenPHP binds it via the Caddyfile.
CMD ["/usr/local/bin/start.sh"]
