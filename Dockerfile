# Use PHP 8.1 with Apache
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for routing
RUN a2enmod rewrite

# Suppress Apache FQDN warning in container
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Override PHP upload limits so app-level validation handles large files gracefully.
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache to serve only /public while keeping existing asset URLs.
RUN echo 'DocumentRoot /var/www/html/public\n\
<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>\n\
Alias /styles /var/www/html/styles\n\
<Directory /var/www/html/styles>\n\
    Require all granted\n\
</Directory>\n\
Alias /js /var/www/html/js\n\
<Directory /var/www/html/js>\n\
    Require all granted\n\
</Directory>\n\
Alias /uploads /var/www/html/uploads\n\
<Directory /var/www/html/uploads>\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/cardinalstage.conf \
    && a2enconf cardinalstage

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
