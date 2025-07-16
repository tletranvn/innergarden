#!/bin/bash
# Script de sauvegarde complète (MySQL + MongoDB)
# Utilisation : ./backup_complete.sh

# Configuration
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_ROOT="./backups"
LOG_FILE="$BACKUP_ROOT/backup_log_${DATE}.log"

# Créer le dossier de sauvegarde principal
mkdir -p $BACKUP_ROOT

# Fonction de logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

log "Début de la sauvegarde complète"

# Vérifier que Docker Compose est en cours d'exécution
if ! docker compose ps | grep -q "Up"; then
    log "Docker Compose n'est pas en cours d'exécution"
    exit 1
fi

# Sauvegarde MySQL
log "Sauvegarde MySQL..."
./scripts/backup_mysql.sh
if [ $? -eq 0 ]; then
    log "Sauvegarde MySQL terminée"
else
    log "Erreur lors de la sauvegarde MySQL"
fi

# Sauvegarde MongoDB
log "Sauvegarde MongoDB..."
./scripts/backup_mongodb.sh
if [ $? -eq 0 ]; then
    log "Sauvegarde MongoDB terminée"
else
    log "Erreur lors de la sauvegarde MongoDB"
fi

# Sauvegarde des uploads (images)
log "Sauvegarde des fichiers uploadés..."
UPLOADS_BACKUP="$BACKUP_ROOT/uploads/uploads_backup_${DATE}.tar.gz"
mkdir -p $BACKUP_ROOT/uploads
tar -czf $UPLOADS_BACKUP public/uploads/
if [ $? -eq 0 ]; then
    log "Sauvegarde des uploads terminée : $UPLOADS_BACKUP"
else
    log "Erreur lors de la sauvegarde des uploads"
fi

# Nettoyage des anciens logs (garder 30 jours)
find $BACKUP_ROOT -name "backup_log_*.log" -type f -mtime +30 -delete

log "Sauvegarde complète terminée"
log "Taille des sauvegardes :"
du -sh $BACKUP_ROOT/*
