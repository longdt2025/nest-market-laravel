FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libonig-dev libzip-dev libpng-dev libicu-dev \
    && docker-php-ext-install -j2 pdo_mysql mbstring zip gd intl bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/entrypoint.sh /usr/local/bin/nest-entrypoint
RUN chmod +x /usr/local/bin/nest-entrypoint

WORKDIR /var/www/html
ENTRYPOINT ["nest-entrypoint"]
CMD ["php-fpm"]
