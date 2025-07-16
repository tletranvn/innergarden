#!/bin/bash
# Script de sauvegarde MongoDB pour Docker
# Utilisation : ./backup_mongodb.sh

# Configuration
DB_NAME="innergarden_mongodb"
BACKUP_DIR="./backups/mongodb"
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FOLDER="innergarden_mongo_backup_${DATE}"

# Credentials MongoDB (depuis .env.docker.local)
MONGO_USER="root"
MONGO_PASSWORD="root"
MONGO_AUTH_DB="admin"

# Créer le dossier de sauvegarde s'il n'existe pas
mkdir -p $BACKUP_DIR

# Sauvegarde MongoDB via Docker
echo "Sauvegarde de MongoDB en cours..."
docker compose exec -T mongodb mongodump --db $DB_NAME --username $MONGO_USER --password $MONGO_PASSWORD --authenticationDatabase $MONGO_AUTH_DB --out /tmp/backup

# Copier la sauvegarde du conteneur vers l'hôte
docker compose cp mongodb:/tmp/backup/$DB_NAME $BACKUP_DIR/$BACKUP_FOLDER

# Vérification du succès
if [ $? -eq 0 ]; then
    echo "Sauvegarde MongoDB créée : $BACKUP_DIR/$BACKUP_FOLDER"
    
    # Compression de la sauvegarde
    cd $BACKUP_DIR
    tar -czf $BACKUP_FOLDER.tar.gz $BACKUP_FOLDER
    rm -rf $BACKUP_FOLDER
    echo "Sauvegarde compressée : $BACKUP_DIR/$BACKUP_FOLDER.tar.gz"
    
    # Nettoyage des anciennes sauvegardes (garder 7 jours)
    find $BACKUP_DIR -name "*.tar.gz" -type f -mtime +7 -delete
    echo "Anciennes sauvegardes supprimées (>7 jours)"
    
    # Nettoyage du conteneur
    docker compose exec -T mongodb rm -rf /tmp/backup
else
    echo "Erreur lors de la sauvegarde MongoDB"
    exit 1
fi
