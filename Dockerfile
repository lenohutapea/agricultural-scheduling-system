# Agricultural Scheduling System: PHP 8.3 on Apache
FROM php:8.3-apache

# MySQL driver for PDO, production PHP settings, quiet Apache
RUN docker-php-ext-install pdo_mysql \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'expose_php = Off\ndate.timezone = Asia/Jakarta\n' > "$PHP_INI_DIR/conf.d/app.ini"

COPY docker/apache.conf /etc/apache2/conf-available/app.conf
RUN a2enmod headers && a2enconf app

COPY --chown=www-data:www-data . /var/www/html/

# Docker marks the container unhealthy if the app or its database stops answering
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
  CMD curl -fsS http://localhost/healthz.php || exit 1

EXPOSE 80
