FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip nginx libpq-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_pgsql pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# php-fpm unix socket (no TCP port exposed — Render only finds nginx)
RUN mkdir -p /run/php \
    && printf "[www]\nlisten = /run/php/php-fpm.sock\nlisten.owner = www-data\nlisten.group = www-data\nlisten.mode = 0660\nclear_env = no\ncatch_workers_output = yes\nphp_admin_value[error_log] = /dev/stderr\nphp_admin_flag[log_errors] = on\n" > /usr/local/etc/php-fpm.d/zz-unix-socket.conf

WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --optimize-autoloader --no-dev \
    && mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache \
    && chown -R www-data:www-data /var/www/html

COPY nginx.conf /etc/nginx/sites-available/default
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
