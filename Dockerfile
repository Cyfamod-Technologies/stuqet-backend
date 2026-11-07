FROM php:8.4-fpm AS production

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
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
    && docker-php-ext-install \
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
        pcntl

# Built into the official PHP base image and available without separate install commands:
# ctype, json, openssl, tokenizer, zlib

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application source
COPY . .


# Ensure entrypoint script is copied before installing Composer deps
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh



# Install PHP dependencies
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# Set proper permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
    
# Copy configuration files
COPY ./docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini



# Expose ports
EXPOSE 80

# will manage Laravel setup and Supervisor
# CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
CMD ["/usr/local/bin/entrypoint.sh"]

# CI adds test dependencies without including them in the deployed image.
FROM production AS ci
RUN composer install --no-scripts --optimize-autoloader --no-interaction
CMD ["sh", "docker/ci.sh"]

# Keep an untargeted Docker build suitable for Dokploy deployment.
FROM production AS runtime
