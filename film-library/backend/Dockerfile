FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY includes/ /var/www/html/includes/
COPY public/ /var/www/html/public/
COPY sql/ /var/www/html/sql/

RUN mkdir -p /var/www/html/frontend

EXPOSE 80
