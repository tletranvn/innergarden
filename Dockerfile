FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure intl
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    intl \
    opcache \
    gd

# Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install APCu
RUN pecl install apcu && docker-php-ext-enable apcu

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set environment variables
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Configure Apache
RUN a2enmod rewrite
RUN a2enmod headers

# Create Apache configuration for Symfony
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /app/public\n\
    DirectoryIndex index.php\n\
    <Directory /app/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
    <Directory /app/var>\n\
        Require all denied\n\
    </Directory>\n\
    ErrorLog /var/log/apache2/error.log\n\
    CustomLog /var/log/apache2/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /app/var /app/public
RUN chmod -R 755 /app/var /app/public

# Clear cache
RUN php bin/console cache:clear --env=prod --no-debug || true

# Configure Apache to use dynamic port
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf

# Create startup script
RUN echo '#!/bin/bash\n\
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/ports.conf\n\
sed -i "s/\*:80/*:$PORT/g" /etc/apache2/sites-available/000-default.conf\n\
apache2-foreground' > /start.sh && chmod +x /start.sh

CMD ["/start.sh"]