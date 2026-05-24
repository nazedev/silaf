FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN a2enmod rewrite

COPY . /var/www/html/
RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
