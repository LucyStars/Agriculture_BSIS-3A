FROM php:8.3-apache
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql && rm -rf /var/lib/apt/lists/*
ENV PORT=10000
RUN sed -i s/80/${PORT}/g /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
RUN sed -i s!/var/www/html!/var/www/html/public!g /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/
