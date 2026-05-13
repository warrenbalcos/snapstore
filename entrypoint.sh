#!/bin/sh
set -e

# Ensure .env exists (Laravel's dotenv loader expects it)
touch .env

# Replace PORT in nginx config
PORT=${PORT:-8000}
sed -i "s/PORT_PLACEHOLDER/$PORT/" /etc/nginx/sites-available/default

# Run migrations
php artisan migrate --force

# Start php-fpm and nginx
php-fpm -D
nginx -g "daemon off;"
