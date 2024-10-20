# Use PHP 8.1 with Apache as the base image
FROM php:8.1-apache

# Install the required packages for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite for URL rewriting
RUN a2enmod rewrite

# Set permissions for the uploads directory
RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads

# Copy application files to the Apache document root
COPY backend/ /var/www/html/
COPY public/ /var/www/html/public/
COPY db.sql /var/www/html/
COPY .env /var/www/html/

# Set the working directory
WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --optimize-autoloader

# CMD php migrations.php 

# Expose port 80
EXPOSE 80
