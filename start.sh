#!/bin/bash

# Ensure var/log and var/cache directories exist with proper permissions
# Only set permissions if running as root (Heroku), skip if running as www-data (Docker Compose)
if [ "$(id -u)" = "0" ]; then
    mkdir -p /var/www/var/log /var/www/var/cache
    chown -R www-data:www-data /var/www/var
    chmod -R 775 /var/www/var
fi

# Set default port to 80 if PORT is not defined (local development)
# Heroku fournit le port via la variable d'environnement $PORT.
if [ -z "$PORT" ]; then
    PORT=80
fi

# 1. Modifiez la directive Listen globale (souvent dans ports.conf ou apache2.conf)
# Remplace toutes les occurrences de "Listen 80" par "Listen $PORT"
# Le 'g' est pour global, le 'i' pour in-place.
# Nous utilisons find pour être sûr de cibler le bon fichier, car le chemin peut varier.
find /etc/apache2/ -type f -name "*.conf" -exec sed -i "s/^Listen 80$/Listen $PORT/g" {} \; || true

# 2. Modifiez le VirtualHost dans 000-default.conf (pour cibler le port $PORT)
# Cette ligne est déjà dans votre script et est correcte si vous avez mis 80 dans 000-default.conf.
sed -i "s/\*:80/*:$PORT/g" /etc/apache2/sites-available/000-default.conf

# Désactiver mpm_event et mpm_worker, s'assurer que seul mpm_prefork est activé.
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2enmod mpm_prefork || true

# --- DÉBOGAGE ---
echo "==== DEBUG MPM et PORTS au démarrage (`date`) ===="
echo "Port Heroku (\$PORT): $PORT"
echo "Contenu de /etc/apache2/ports.conf (si existe) :"
cat /etc/apache2/ports.conf || echo "ports.conf non trouvé ou erreur de lecture." # Pour vérifier si le sed a fonctionné
echo "Contenu de /etc/apache2/sites-available/000-default.conf (extrait Listen/VirtualHost) :"
grep -E "Listen|VirtualHost" /etc/apache2/sites-available/000-default.conf || echo "Listen/VirtualHost non trouvé dans 000-default.conf."
ls -l /etc/apache2/mods-enabled/ | grep mpm
apache2ctl configtest || true
# --- FIN DÉBOGAGE ---

# Lancer Apache en premier plan.
exec apache2-foreground