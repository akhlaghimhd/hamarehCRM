FROM php:8.4-apache

RUN set -eux; \
    for i in 1 2 3; do \
        apt-get update && break || sleep 5; \
    done; \
    apt-get install -y --no-install-recommends \
        git unzip \
        libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql zip gd mbstring exif pcntl bcmath intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN sed -i "s|/var/www/html|/var/www/html/public|g" /etc/apache2/sites-available/000-default.conf

EXPOSE 80
