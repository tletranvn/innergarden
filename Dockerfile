FROM php:8.3-apache

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

# Ensuite, copier le reste de l'application
COPY . /var/www

# Installer les dépendances Composer
WORKDIR /var/www

# Nettoyer le cache de Symfony
RUN rm -rf var/cache/*

# Définir un DATABASE_URL pour le BUILD qui ne nécessite pas de connexion réelle
# Utilisation de SQLite en mémoire pour éviter l'erreur de connexion MySQL
ENV DATABASE_URL="sqlite:///:memory:"

# NOUVEAU : Définir un MONGODB_URL pour le BUILD
# Cela doit être syntaxiquement valide mais ne pointer vers rien de réel
ENV MONGODB_URL="mongodb://localhost:27017/fake_db"

# NOUVEAU : Définir un CLOUDINARY_URL pour le BUILD
# Cela doit être syntaxiquement valide mais ne pointer vers rien de réel
ENV CLOUDINARY_URL="cloudinary://fake_key:fake_secret@fake_cloud"

# Configure PHP timezone (will be overridden by TZ env var if set)
RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini

# Installer les dépendances Composer sans dev dependencies
# Définir APP_ENV=prod spécifiquement pour cette commande RUN
# Cela garantit que les scripts Symfony liés au build se comportent comme en prod

# Install all Composer dependencies (including dev dependencies for local development)
RUN composer install --optimize-autoloader --no-interaction
# RUN APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader --no-interaction

# CORRECTION ICI : Définir le document root d'Apache à /var/www/public
ENV APACHE_DOCUMENT_ROOT=/var/www/public

# Copy and use the existing Apache configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Update the document root in the copied config to match our structure
RUN sed -i 's|/var/www/html|/var/www|g' /etc/apache2/sites-available/000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Ensure var/log directory exists with proper permissions
RUN mkdir -p /var/www/var/log && \
    chown -R www-data:www-data /var/www/var && \
    chmod -R 775 /var/www/var

# Use the start script as entrypoint
CMD ["/usr/local/bin/start.sh"]