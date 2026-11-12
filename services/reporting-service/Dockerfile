# Microservice Laravel - image de production-like (dev)
FROM php:8.3-fpm-alpine

# Dependances systeme : pgsql, bcmath, intl, zip (excel/dompdf), redis
RUN apk add --no-cache \
        postgresql-dev linux-headers $PHPIZE_DEPS \
        icu-dev libzip-dev oniguruma-dev curl-dev \
    && docker-php-ext-install pdo_pgsql bcmath intl zip mbstring exif pcntl \
    && apk del $PHPIZE_DEPS linux-headers

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Dependencies d'abord (cache Docker)
COPY composer.json composer.lock* ./
RUN composer update --no-dev --prefer-dist --no-interaction --no-scripts || true

# Code applicatif
COPY . .
RUN composer dump-autoloader --optimize --no-dev 2>/dev/null || composer dump-autoload --optimize

# Permissions storage
RUN mkdir -p storage/app storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 8000

# Point d'entree : migrations idempotentes puis PHP-FPM
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
