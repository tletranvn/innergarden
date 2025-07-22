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

# Nettoyer le cache de Symfony
RUN rm -rf var/cache/*

# Définir un DATABASE_URL pour le BUILD qui ne nécessite pas de connexion réelle
# Utilisation de SQLite en mémoire pour éviter l'erreur de connexion MySQL
ENV DATABASE_URL="sqlite:///:memory:"

# NOUVEAU : Définir un MONGODB_URL pour le BUILD
# Cela doit être syntaxiquement valide mais ne pointer vers rien de réel
ENV MONGODB_URL="mongodb://localhost:27017/fake_db"

# Installer les dépendances Composer sans dev dependencies
# Définir APP_ENV=prod spécifiquement pour cette commande RUN
# Cela garantit que les scripts Symfony liés au build se comportent comme en prod

RUN APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader --no-interaction

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

CMD ["start.sh"]