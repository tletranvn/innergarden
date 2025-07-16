#!/bin/bash
# Script de restauration MongoDB
# Utilisation : ./restore_mongodb.sh <fichier_sauvegarde.tar.gz>

if [ $# -eq 0 ]; then
    echo "Usage: $0 <fichier_sauvegarde.tar.gz>"
    echo "Exemples de fichiers disponibles :"
    ls -la ./backups/mongodb/
    exit 1
fi

BACKUP_FILE=$1
DB_NAME="innergarden_mongodb"

# Credentials MongoDB (depuis .env.docker.local)
MONGO_USER="root"
MONGO_PASSWORD="root"
MONGO_AUTH_DB="admin"

# Vérifier que le fichier existe
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Fichier de sauvegarde non trouvé : $BACKUP_FILE"
    exit 1
fi

echo "Restauration de MongoDB depuis : $BACKUP_FILE"
echo "ATTENTION : Cette opération va écraser la base de données actuelle !"
read -p "Continuer ? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Restauration annulée"
    exit 1
fi

# Créer un dossier temporaire
TEMP_DIR="/tmp/mongo_restore_$$"
mkdir -p $TEMP_DIR

# Décompresser
echo "🔄 Décompression..."
tar -xzf $BACKUP_FILE -C $TEMP_DIR

# Trouver le dossier de données
DATA_DIR=$(find $TEMP_DIR -name "*.bson" -exec dirname {} \; | head -1)
if [ -z "$DATA_DIR" ]; then
    echo "Impossible de trouver les données MongoDB dans l'archive"
    rm -rf $TEMP_DIR
    exit 1
fi

# Copier dans le conteneur
echo "Copie des données dans le conteneur..."
docker compose cp $DATA_DIR mongodb:/tmp/restore/

# Restaurer
echo "Restauration..."
docker compose exec -T mongodb mongorestore --db $DB_NAME --username $MONGO_USER --password $MONGO_PASSWORD --authenticationDatabase $MONGO_AUTH_DB --drop /tmp/restore/

if [ $? -eq 0 ]; then
    echo "Restauration MongoDB terminée avec succès"
else
    echo "Erreur lors de la restauration MongoDB"
fi

# Nettoyage
rm -rf $TEMP_DIR
docker compose exec -T mongodb rm -rf /tmp/restore
