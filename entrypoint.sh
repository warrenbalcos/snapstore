#!/bin/sh
set -e

mkdir -p /run/php

PORT=${PORT:-8000}
sed -i "s/PORT_PLACEHOLDER/$PORT/" /etc/nginx/sites-available/default

php artisan migrate --force

php-fpm -D
nginx -g "daemon off;"
