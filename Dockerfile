FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy application files
COPY . /var/www/html/

# Ensure Apache serves on Render's $PORT (defaults to 8080)
CMD ["sh", "-c", "sed -i \"s/80/${PORT:-8080}/g\" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground"]
