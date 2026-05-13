#!/bin/sh
set -e

# Replace PORT placeholder in nginx config
PORT=${PORT:-8000}
sed "s/PORT_PLACEHOLDER/$PORT/" /etc/nginx/sites-available/default > /etc/nginx/sites-available/default.tmp
mv /etc/nginx/sites-available/default.tmp /etc/nginx/sites-available/default

# Run migrations
php artisan migrate --force

# Start php-fpm and nginx
php-fpm -D
nginx -g "daemon off;"
