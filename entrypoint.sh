#!/bin/sh
set -e

mkdir -p /run/php

PORT=${PORT:-8000}
sed -i "s/PORT_PLACEHOLDER/$PORT/" /etc/nginx/sites-available/default

# Generate .env from environment so Laravel always has config
# (php-fpm clear_env can block env vars; .env file is the reliable fix)
cat > /var/www/html/.env <<EOF
APP_NAME=Snapstore
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=${SESSION_DRIVER:-array}
CACHE_STORE=${CACHE_STORE:-array}
LOG_CHANNEL=stderr
EOF

chown www-data:www-data /var/www/html/.env

echo "=== Running migrations ==="
php artisan migrate --force
echo "=== Migrations done ==="

echo "=== Starting php-fpm + nginx ==="
php-fpm -D
nginx -g "daemon off;"
