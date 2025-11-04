#!/bin/bash

# Script pour publier automatiquement les articles programmés
# Ce script doit être exécuté toutes les 5 minutes via un cronjob

# Déterminer le répertoire du projet (le parent du dossier scripts)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Charger les variables d'environnement si le fichier .env existe
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | xargs)
fi

# Fichier de log dans /tmp pour éviter les problèmes de permissions
LOG_FILE="/tmp/innergarden_publish_articles.log"

# Créer le fichier de log s'il n'existe pas
touch "$LOG_FILE" 2>/dev/null || true

# Timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Exécuter la commande Symfony
echo "[$TIMESTAMP] Exécution de la commande de publication..." >> "$LOG_FILE"

cd "$PROJECT_DIR" || exit 1

# Détecter si Docker Compose est disponible et le conteneur est en cours d'exécution
if command -v docker &> /dev/null && docker compose ps www 2>/dev/null | grep -q "Up"; then
    # Environnement Docker - utiliser docker compose exec
    echo "[$TIMESTAMP] Environnement Docker détecté, utilisation de docker compose" >> "$LOG_FILE"
    docker compose exec -T www php bin/console app:publish-scheduled-articles >> "$LOG_FILE" 2>&1
else
    # Environnement natif ou Heroku - utiliser php directement
    echo "[$TIMESTAMP] Environnement natif détecté, utilisation de php directement" >> "$LOG_FILE"
    php bin/console app:publish-scheduled-articles >> "$LOG_FILE" 2>&1
fi

# Vérifier le code de sortie
if [ $? -eq 0 ]; then
    echo "[$TIMESTAMP] Commande exécutée avec succès" >> "$LOG_FILE"
else
    echo "[$TIMESTAMP] ERREUR lors de l'exécution de la commande" >> "$LOG_FILE"
fi

echo "" >> "$LOG_FILE"
