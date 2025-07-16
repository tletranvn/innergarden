FROM php:8.3-apache

# Set ServerName to avoid warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install system dependencies
RUN apt-get update \
    && apt-get install -qq -y --no-install-recommends \
    cron \
    locales coreutils apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev \
    # Ajouter dépendances SSL nécessaires pour pecl mongodb avec support SSL
    libssl-dev libcurl4-openssl-dev pkg-config \
    && apt-get upgrade -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

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

# Install apcu
RUN pecl install apcu && docker-php-ext-enable apcu

# Installer et activer l'extension MongoDB avec support SSL (libssl-dev etc. installés avant)
# permet à pecl mongodb d’être compilé avec le support SSL nécessaire pour SCRAM-SHA-256.
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install install-php-extensions utility
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions amqp

# --- DÉBUT DES MODIFICATIONS POUR LA COMPATIBILITÉ HEROKU ET LE FONCTIONNEMENT SYMFONY ---

# Heroku déploie l'application dans le répertoire /app.
# Par conséquent, le WORKDIR doit être défini sur /app pour que les chemins relatifs fonctionnent correctement.
# Pour localhost, le volume mount ./:/var/www rendra /var/www synchronisé avec ton projet.
# Mais pour Heroku, qui ne monte pas de volume, /app est l'emplacement standard.
WORKDIR /app

# Copie le contenu de ton projet (depuis le contexte de build '.') vers le WORKDIR '/app'.
# Cela garantit que tous les fichiers de ton application sont présents dans le conteneur Heroku.
COPY . /app

# Exécuter composer install DANS le conteneur APRES la copie
# Cela assure que les dépendances sont installées pour l'architecture cible du conteneur (amd64)
# et qu'elles sont incluses dans l'image finale pour Heroku.
# Pour le développement local, ce n'est pas strictement nécessaire si tu as déjà tes vendors localement,
# mais c'est une bonne pratique pour les builds de l'image.
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# Active la prise en charge des fichiers .htaccess par Apache. C'est crucial pour la réécriture d'URL de Symfony.
# Sans cela, Apache ignorerait les règles de réécriture dans public/.htaccess, pouvant mener à des erreurs 403/404.
RUN sed -i -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# L'image php:apache utilise /var/www/html comme DocumentRoot par défaut.
# Heroku s'attend à ce que le point d'entrée de l'application soit accessible via le DocumentRoot.
# Le code Symfony est dans /app, et le point d'entrée web est /app/public/.
# Cette ligne supprime le dossier par défaut de l'image et crée un lien symbolique
# de /var/www/html vers le dossier `public` de Symfony qui est maintenant dans /app/public.
# Ainsi, Apache servira correctement l'application depuis son vrai point d'entrée, que ce soit localement
# (via le volume mount de /var/www, et le lien symbolique interne au conteneur vers /app/public) ou sur Heroku.
RUN rm -rf /var/www/html \
    && ln -s /app/public /var/www/html

# Set permissions for Symfony cache and logs
# Ajuste les permissions pour les dossiers 'var' (cache/logs de Symfony) et 'public' (actifs web).
# Il est crucial que le processus 'www-data' (Apache) ait les droits d'écriture sur ces dossiers.
# Ajout de /var/www/html dans les permissions pour s'assurer que le nouveau DocumentRoot est accessible.
# Bien que 777 soit très permissif, c'est souvent utilisé pour déboguer sur Heroku.
# Pour une production sérieuse, tu pourrais viser 775 ou des permissions plus strictes si possible.
RUN chown -R www-data:www-data /app/var /app/public /var/www/html \
    && chmod -R 777 /app/var /app/public /var/www/html

# --- FIN DES MODIFICATIONS ---

# If building for production, consider 'RUN APP_ENV=prod composer install --no-dev --optimize-autoloader'
# Ce RUN composer install --no-dev --prefer-dist --optimize-autoloader a été déplacé
# après le COPY . /app pour assurer que les vendors sont installés dans l'image pour Heroku.
# Tu peux garder cette ligne commentée si tu la veux comme rappel pour une build prod dédiée.
# RUN composer install --no-dev --optimize-autoloader