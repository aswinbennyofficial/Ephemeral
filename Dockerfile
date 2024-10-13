# Use the official PHP image with Apache
FROM php:8.1-apache

# Create the uploads directory
RUN mkdir -p /var/www/html/uploads

# Set the ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy HTML, CSS, JS, and uploads to the Apache directory
COPY app /var/www/html/

# Set permissions for the uploads directory
RUN chown -R www-data:www-data /var/www/html/uploads

# Set permissions for the web root
RUN chown -R www-data:www-data /var/www/html

# Set read permissions for the directory and files
RUN chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
