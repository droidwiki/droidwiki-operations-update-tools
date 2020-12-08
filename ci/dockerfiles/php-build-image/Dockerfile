FROM composer:latest

LABEL maintainer="florian@go2tech.de"

RUN apk add --no-cache --no-progress libpng-dev tar

RUN docker-php-ext-install gd pcntl pdo_mysql mysqli intl
