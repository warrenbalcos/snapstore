#!/bin/sh
set -e

# Create .env if missing (excluded by .dockerignore)
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Replace PORT in nginx config
PORT=${PORT:-8000}
sed -i "s/PORT_PLACEHOLDER/$PORT/" /etc/nginx/sites-available/default

# Run migrations
php artisan migrate --force

# Start php-fpm and nginx
php-fpm -D
nginx -g "daemon off;"
