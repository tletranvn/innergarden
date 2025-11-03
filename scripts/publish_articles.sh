#!/bin/bash

# Script pour publier automatiquement les articles programmés
# Ce script doit être exécuté toutes les 5 minutes via un cronjob

# Déterminer le répertoire du projet (le parent du dossier scripts)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Charger les variables d'environnement si le fichier .env existe
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | xargs)
fi

# Fichier de log
LOG_FILE="$PROJECT_DIR/var/log/publish_articles.log"

# Créer le dossier de logs s'il n'existe pas
mkdir -p "$PROJECT_DIR/var/log"

# Timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Exécuter la commande Symfony
echo "[$TIMESTAMP] Exécution de la commande de publication..." >> "$LOG_FILE"

cd "$PROJECT_DIR" || exit 1

# Exécuter la commande via php bin/console
php bin/console app:publish-scheduled-articles >> "$LOG_FILE" 2>&1

# Vérifier le code de sortie
if [ $? -eq 0 ]; then
    echo "[$TIMESTAMP] Commande exécutée avec succès" >> "$LOG_FILE"
else
    echo "[$TIMESTAMP] ERREUR lors de l'exécution de la commande" >> "$LOG_FILE"
fi

echo "" >> "$LOG_FILE"
