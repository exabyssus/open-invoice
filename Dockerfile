# open-invoice (Laravel 13 + Filament 5, PHP 8.5) image for Coolify. Build context = repo root.
# Persistent volume in Coolify: /var/www/app/storage (company logos in storage/app/private, dompdf font cache, logs).
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --ignore-platform-reqs --optimize-autoloader

FROM php:8.5-apache
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath gd intl pdo_mysql opcache \
 && a2enmod rewrite remoteip \
 && sed -ri 's!/var/www/html!/var/www/app/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '<Directory /var/www/app/public>\n  AllowOverride All\n</Directory>\nServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-enabled/app.conf \
 && printf 'RemoteIPHeader CF-Connecting-IP\nRemoteIPInternalProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16\n' > /etc/apache2/conf-enabled/remoteip.conf \
 && printf 'memory_limit=512M\nexpose_php=Off\nupload_max_filesize=8M\npost_max_size=10M\n' > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/app
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private bootstrap/cache \
 && php artisan filament:assets \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["sh", "docker/entrypoint.sh"]
CMD ["apache2-foreground"]
