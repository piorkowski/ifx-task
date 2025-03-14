FROM php:8.3-fpm-alpine

USER root

RUN apk update && \
    apk add --no-cache git unzip postgresql-dev oniguruma-dev linux-headers $PHPIZE_DEPS && \
    pecl install \
        apcu \
        xdebug \
        redis && \
    docker-php-ext-install \
        bcmath \
        ctype \
        intl \
        mbstring \
        pcntl \
        pdo_pgsql && \
    docker-php-ext-enable \
        apcu \
        intl \
        xdebug \
        redis

RUN mkdir -p /app/var/log

COPY ./docker/php/conf.d/xdebug.ini $PHP_INI_DIR/conf.d/xdebug.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ENV PHP_IDE_CONFIG="serverName=app"

WORKDIR /app

COPY . /app

RUN composer install --no-interaction --prefer-dist --optimize-autoloader && \
    composer config --no-plugins allow-plugins.infection/extension-installer true

USER www-data
