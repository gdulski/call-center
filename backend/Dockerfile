FROM php:8.2-fpm

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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Copy composer files first
COPY composer.json composer.lock ./

RUN composer validate


# Install dependencies (allow dev dependencies for development)
# RUN composer install --no-interaction --optimize-autoloader

# # Copy application code
# COPY . .

# # Set permissions
# RUN chown -R www-data:www-data /var/www/html \
#     && chmod -R 755 /var/www/html

# # Expose port
# EXPOSE 8000

# # Start Symfony server
# CMD ["symfony", "server:start", "--host=0.0.0.0", "--port=8000", "--no-tls"] 