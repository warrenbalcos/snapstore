#!/bin/sh
set -e

mkdir -p /run/php

PORT=${PORT:-8000}
sed -i "s/PORT_PLACEHOLDER/$PORT/" /etc/nginx/sites-available/default

# Generate .env from environment only if missing (Render has no .env file;
# local dev has one via volume mount with APP_KEY etc already set)
if [ ! -f /var/www/html/.env ]; then
    # Validate required env vars
    if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_PASSWORD" ]; then
        echo "ERROR: Missing required database env vars."
        echo "  DB_HOST=$DB_HOST"
        echo "  DB_PORT=$DB_PORT"
        echo "  DB_DATABASE=$DB_DATABASE"
        echo "  DB_USERNAME=$DB_USERNAME"
        echo "  DB_PASSWORD=${DB_PASSWORD:+***set***}"
        echo "Check that the Render database is created and linked to this service."
        exit 1
    fi

    if [ -z "$APP_KEY" ]; then
        echo "ERROR: APP_KEY is not set."
        exit 1
    fi

    cat > /var/www/html/.env <<EOF
APP_NAME=Snapstore
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

DB_CONNECTION=${DB_CONNECTION:-pgsql}
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
    echo "Generated .env from environment variables"
fi

echo "=== Running migrations ==="
php artisan migrate --force
echo "=== Migrations done ==="

echo "=== Starting php-fpm + nginx ==="
php-fpm -D
nginx -g "daemon off;"
