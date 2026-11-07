FROM php:8.4-fpm AS base

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libxslt-dev \
    libcurl4-openssl-dev \
    libpq-dev \
    zlib1g-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        bcmath \
        curl \
        dom \
        fileinfo \
        gd \
        intl \
        xsl \
        xml \
        zip \
        pcntl \
    && rm -rf /var/lib/apt/lists/*

# Built into the official PHP base image and available without separate install commands:
# ctype, json, openssl, tokenizer, zlib

# Install Composer
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Cache dependency installation by manifests, not by application source.
FROM base AS production-dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

FROM production-dependencies AS test-dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

FROM base AS production
COPY . .
COPY --from=production-dependencies /var/www/html/vendor ./vendor
RUN composer dump-autoload --no-dev --no-scripts --optimize \
    && composer check-platform-reqs --no-dev

COPY infra/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY infra/docker/nginx/default.conf /etc/nginx/sites-available/default
COPY infra/docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY infra/docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]

# Building CI also builds and validates its production parent. Only one image
# needs to be exported to the runner; production remains free of dev packages.
FROM production AS ci
COPY --from=test-dependencies /var/www/html/vendor ./vendor
RUN composer dump-autoload --no-scripts --optimize
CMD ["sh", "infra/docker/ci.sh"]

# An untargeted build remains the deployment image.
FROM production AS runtime
