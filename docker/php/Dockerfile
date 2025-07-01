FROM php:8.4-apache

# Set ServerName to avoid warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install system dependencies
RUN apt-get update \
    && apt-get install -qq -y --no-install-recommends \
    cron \
    locales coreutils apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev;

# Generate locales
RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

# Install Composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
    mv composer.phar /usr/local/bin/composer

# Install PHP extensions
RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring zip gd xsl && a2enmod rewrite
RUN pecl install apcu && docker-php-ext-enable apcu

# Install install-php-extensions utility
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions amqp

# Set working directory inside the container
WORKDIR /var/www

# Copy your application code into the container
# This is crucial! Your code needs to be in /var/www before composer install runs.
# The 'context: .' in docker-compose.yaml ensures this copies from your project root.
COPY . /var/www

# Install Composer dependencies
# This version keeps dev dependencies for easier development.
# If building for production, consider 'RUN APP_ENV=prod composer install --no-dev --optimize-autoloader'
RUN composer install --optimize-autoloader

# Set permissions for Symfony cache and logs
# Adjust permissions as needed, 777 might be too open for production
RUN chown -R www-data:www-data var public \
    && chmod -R 777 var public