FROM php:7.4-alpine

LABEL maintainer="florian@go2tech.de"

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apk add --no-cache --no-progress libpng-dev tar icu-dev

RUN docker-php-ext-install gd pcntl pdo_mysql mysqli intl
