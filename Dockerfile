# Use official PHP with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Copy current directory contents into the container
COPY . /var/www/html/

# Ensure settings.json is writable by the web server
RUN touch /var/www/html/settings.json && \
    chown www-data:www-data /var/www/html/settings.json && \
    chmod 664 /var/www/html/settings.json

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
