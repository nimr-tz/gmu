FROM node:24-bookworm-slim AS assets

WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM php:8.4-fpm-bookworm

WORKDIR /var/www
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip ca-certificates \
    && docker-php-ext-install -j "$(nproc)" opcache pcntl pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts

COPY artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY --from=assets /build/public/build ./public/build
COPY docker/uploads.ini /usr/local/etc/php/conf.d/gmu-uploads.ini

RUN install -d -o www-data -g www-data \
        bootstrap/cache storage/app/private storage/app/public storage/logs \
        storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data bootstrap/cache storage

USER 33:33
EXPOSE 9000
