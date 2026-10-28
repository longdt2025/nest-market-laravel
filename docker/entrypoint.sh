#!/bin/sh
set -eu
umask 0002

mkdir -p bootstrap/cache storage/app/public/products storage/app/private \
    storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/framework/testing storage/logs

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} +

exec docker-php-entrypoint "$@"
