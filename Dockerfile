FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev unzip git libpng-dev libonig-dev libxml2-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    pkg-config libssl-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install pdo pdo_mysql intl opcache zip gd \
 # MONGODB AVEC PECL 
 && pecl install mongodb \
 && docker-php-ext-enable mongodb \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Copier composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le script start.sh et rendre-le exécutable
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Ensuite, copier le reste de votre application
COPY . /var/www/html/

# Installer les dépendances Composer
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

CMD ["start.sh"]