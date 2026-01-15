FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/admin

# Copy application files
COPY . /var/www/admin

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create necessary directories and set permissions
RUN mkdir -p storage/logs storage/cache storage/uploads storage/backups && \
    chmod -R 775 storage && \
    chown -R www-data:www-data storage

# Copy environment file if it doesn't exist
RUN test -f .env || cp .env.example .env || true

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
