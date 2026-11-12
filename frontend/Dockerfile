FROM php:8.3-cli-alpine

RUN apk add --no-cache postgresql-dev $PHPIZE_DEPS bash git unzip curl nodejs npm \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-enable opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader || composer install --no-scripts --no-autoloader
COPY package.json package-lock.json* ./
RUN npm install
COPY . .
RUN composer dump-autoload --optimize && npm run build

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 3000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=3000"]
