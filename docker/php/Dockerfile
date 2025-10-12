FROM php:8.2-fpm

# Install system dependencies for GCP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    gnupg \
    lsb-release

# Add MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Add GCP SDK for Cloud Storage
RUN curl -sS https://dl.google.com/cloudagents/add-google-cloud-ops-agent-repo.sh | bash \
    && apt-get update && apt-get install -y google-cloud-cli

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files
COPY src/composer.json src/composer.lock ./

# Install dependencies (no-dev for production)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY src/ /var/www/

# Set permissions for GCP
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Create health check file
RUN echo "<?php http_response_code(200); echo 'OK'; ?>" > /var/www/public/health-check.php

EXPOSE 9000
CMD ["php-fpm"]