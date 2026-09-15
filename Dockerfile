FROM php:8.2-apache

# Install required PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Ensure only mpm_prefork is active and enable mod_rewrite
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Configure Apache to listen on dynamic $PORT passed by Railway
RUN sed -ri -e 's!Listen 80!Listen ${PORT}!g' /etc/apache2/ports.conf \
    && sed -ri -e 's!VirtualHost \*:80!VirtualHost *:${PORT}!g' /etc/apache2/sites-available/*.conf

WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure uploads directory exists with write permissions
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads

ENV PORT=8080
EXPOSE 8080

CMD ["apache2-foreground"]
