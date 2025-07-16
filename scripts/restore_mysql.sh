#!/bin/bash
# Script de restauration MySQL
# Utilisation : ./restore_mysql.sh <fichier_sauvegarde.sql.gz>

if [ $# -eq 0 ]; then
    echo "Usage: $0 <fichier_sauvegarde.sql.gz>"
    echo "Exemples de fichiers disponibles :"
    ls -la ./backups/mysql/
    exit 1
fi

BACKUP_FILE=$1
DB_NAME="innergarden"
DB_USER="root"
DB_PASSWORD=

# Vérifier que le fichier existe
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Fichier de sauvegarde non trouvé : $BACKUP_FILE"
    exit 1
fi

echo "Restauration de MySQL depuis : $BACKUP_FILE"
echo "ATTENTION : Cette opération va écraser la base de données actuelle !"
read -p "Continuer ? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Restauration annulée"
    exit 1
fi

# Décompresser et restaurer
echo "Décompression et restauration..."
if [[ $BACKUP_FILE == *.gz ]]; then
    gunzip -c $BACKUP_FILE | docker compose exec -T db mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME
else
    cat $BACKUP_FILE | docker compose exec -T db mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME
fi

if [ $? -eq 0 ]; then
    echo "Restauration MySQL terminée avec succès"
else
    echo "Erreur lors de la restauration MySQL"
    exit 1
fi
