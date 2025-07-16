#!/bin/bash
# Script de sauvegarde MySQL pour Docker
# Utilisation : ./backup_mysql.sh

# Configuration
DB_NAME="innergarden"
DB_USER="root"
DB_PASSWORD=
BACKUP_DIR="./backups/mysql"
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="innergarden_backup_${DATE}.sql"

# Créer le dossier de sauvegarde s'il n'existe pas
mkdir -p $BACKUP_DIR

# Sauvegarde MySQL via Docker
echo "Sauvegarde de MySQL en cours..."
docker compose exec -T db mysqldump -u$DB_USER -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/$BACKUP_FILE

# Vérification du succès
if [ $? -eq 0 ]; then
    echo "Sauvegarde MySQL créée : $BACKUP_DIR/$BACKUP_FILE"
    
    # Compression de la sauvegarde
    gzip $BACKUP_DIR/$BACKUP_FILE
    echo "Sauvegarde compressée : $BACKUP_DIR/$BACKUP_FILE.gz"
    
    # Nettoyage des anciennes sauvegardes (garder 7 jours)
    find $BACKUP_DIR -name "*.sql.gz" -type f -mtime +7 -delete
    echo "Anciennes sauvegardes supprimées (>7 jours)"
else
    echo "Erreur lors de la sauvegarde MySQL"
    exit 1
fi
