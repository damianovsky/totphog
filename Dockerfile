FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-install gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configure Apache
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>' >> /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var 2>/dev/null || mkdir -p /var/www/html/var && chown -R www-data:www-data /var/www/html/var

# Generate random APP_SECRET
RUN echo "APP_ENV=dev" > .env && \
    echo "APP_DEBUG=1" >> .env && \
    echo "APP_SECRET=$(openssl rand -hex 32)" >> .env

EXPOSE 80
