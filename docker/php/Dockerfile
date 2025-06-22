FROM php:8.2-apache

# Apache config
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Dépendances système + extensions PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    cron locales coreutils apt-utils git libicu-dev g++ libpng-dev \
    libxml2-dev libzip-dev libonig-dev libxslt-dev unzip curl \
 && docker-php-ext-configure intl \
 && docker-php-ext-install pdo pdo_mysql mysqli gd opcache intl zip calendar mbstring xsl \
 && a2enmod rewrite \
 && pecl install apcu && docker-php-ext-enable apcu

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Installer une extension via mlocati
RUN curl -Ls https://github.com/mlocati/docker-php-extension-installer/releases/download/2.8.1/install-php-extensions \
    -o /usr/local/bin/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    sync && \
    install-php-extensions amqp



# Rediriger DocumentRoot vers /public
ENV APACHE_DOCUMENT_ROOT=/var/www/public
RUN sed -ri -e 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!/var/www/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www
