FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html && chmod -R 777 /var/www/html/uploads

RUN echo '#!/bin/bash' > /entrypoint.sh && echo 'sed -i "s/80/"$PORT"/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf' >> /entrypoint.sh && echo 'exec apache2-foreground' >> /entrypoint.sh && chmod +x /entrypoint.sh

ENV PORT=8080
EXPOSE 8080

CMD ["/entrypoint.sh"]
