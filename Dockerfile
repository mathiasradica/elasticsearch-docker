FROM php:7.4-apache
COPY ./ /var/www/html
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get -y install git
WORKDIR /var/www/html
CMD composer install && apachectl -D FOREGROUND