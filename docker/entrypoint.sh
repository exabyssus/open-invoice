#!/bin/sh
# Runs on every container start: the storage volume may start empty, then cache config/routes/views for production.
set -e
cd /var/www/app
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
if [ "${APP_ENV:-production}" = "production" ]; then
  php artisan optimize
else
  php artisan optimize:clear >/dev/null
fi
exec "$@"
