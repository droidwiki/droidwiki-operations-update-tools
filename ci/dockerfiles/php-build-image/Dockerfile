FROM composer:1

LABEL maintainer="florian@go2tech.de"

RUN apk add --no-cache --no-progress libpng-dev tar icu-dev

RUN docker-php-ext-install gd pcntl pdo_mysql mysqli intl
