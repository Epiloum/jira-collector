FROM php:8.1.19-apache

RUN docker-php-ext-install mysqli

# Install dotenv library by Composer
WORKDIR /var/www/html
COPY ./viewer/* .
COPY .env .