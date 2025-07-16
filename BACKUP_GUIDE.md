# GUIDE DE SAUVEGARDE - INNER GARDEN

## Scripts disponibles

### Sauvegardes
- `./scripts/backup_mysql.sh` - Sauvegarde MySQL uniquement
- `./scripts/backup_mongodb.sh` - Sauvegarde MongoDB uniquement  
- `./scripts/backup_complete.sh` - Sauvegarde complète (MySQL + MongoDB + uploads)

### Restaurations
- `./scripts/restore_mysql.sh <fichier>` - Restaure MySQL depuis un fichier
- `./scripts/restore_mongodb.sh <fichier>` - Restaure MongoDB depuis un fichier

## Structure des sauvegardes

```
backups/
├── mysql/
│   ├── innergarden_backup_20250715_140000.sql.gz
│   └── innergarden_backup_20250715_020000.sql.gz
├── mongodb/
│   ├── innergarden_mongo_backup_20250715_140000.tar.gz
│   └── innergarden_mongo_backup_20250715_020000.tar.gz
├── uploads/
│   ├── uploads_backup_20250715_140000.tar.gz
│   └── uploads_backup_20250715_020000.tar.gz
└── backup_log_20250715_140000.log
```

## Utilisation rapide

### Sauvegarde complète
```bash
./scripts/backup_complete.sh
```

### Restauration MySQL
```bash
./scripts/restore_mysql.sh backups/mysql/innergarden_backup_20250715_140000.sql.gz
```

### Restauration MongoDB
```bash
./scripts/restore_mongodb.sh backups/mongodb/innergarden_mongo_backup_20250715_140000.tar.gz
```

## ⚡ Automatisation

### Installation des tâches cron
```bash
crontab -e
# Copier le contenu de scripts/crontab_example.txt
```

### Tâches automatisées
- **Sauvegarde quotidienne** : 2h du matin
- **Sauvegarde MySQL** : Toutes les 6h
- **Sauvegarde MongoDB** : Toutes les 4h
- **Nettoyage automatique** : Dimanche minuit

## Sécurité des sauvegardes

### Bonnes pratiques
1. **Chiffrement** : Chiffrer les sauvegardes sensibles
2. **Stockage externe** : Sauvegarder sur un autre serveur/cloud
3. **Tests réguliers** : Tester les restaurations
4. **Monitoring** : Surveiller les logs de sauvegarde

### Commandes de sécurité
```bash
# Chiffrer une sauvegarde
gpg --cipher-algo AES256 --compress-algo 1 --symmetric backup.sql.gz

# Synchroniser vers un serveur distant
rsync -avz backups/ user@remote:/path/to/backups/
```

## Monitoring

### Vérifier les sauvegardes
```bash
# Taille des sauvegardes
du -sh backups/*

# Dernières sauvegardes
ls -la backups/mysql/ | head -5
ls -la backups/mongodb/ | head -5

# Logs de sauvegarde
tail -f backups/backup_log_*.log
```

## Résolution de problèmes

### Erreurs communes
1. **Docker non démarré** : `docker compose up -d`
2. **Permissions** : `chmod +x scripts/*.sh`
3. **Espace disque** : Vérifier avec `df -h`
4. **Conteneur non accessible** : Vérifier avec `docker compose ps`

### Tests de restauration
```bash
# Test restauration MySQL (base de test)
docker compose exec db mysql -uroot -proot -e "CREATE DATABASE test_restore;"
# Restaurer dans test_restore au lieu de innergarden
```
