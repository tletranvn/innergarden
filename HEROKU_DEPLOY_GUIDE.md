# Guide de déploiement sur Heroku

## Prérequis

1. **Compte Heroku** : [https://heroku.com](https://heroku.com)
2. **Heroku CLI** : `brew install heroku/brew/heroku`
3. **Git** : Votre projet doit être dans un dépôt Git

## Configuration des bases de données

### MySQL (JawsDB ou ClearDB)
```bash
# Ajouter l'add-on JawsDB MySQL (gratuit jusqu'à 5MB)
heroku addons:create jawsdb:kitefin

# Ou ClearDB MySQL (gratuit jusqu'à 5MB)
heroku addons:create cleardb:ignite
```

### MongoDB (MongoDB Atlas)
```bash
# Ajouter l'add-on MongoDB Atlas (gratuit jusqu'à 512MB)
heroku addons:create mongolab:sandbox
```

## Déploiement étape par étape

### 1. Installation et connexion Heroku
```bash
# Se connecter à Heroku
heroku login

# Créer une nouvelle app
heroku create votre-nom-app

# Ou utiliser une app existante
heroku git:remote -a votre-nom-app
```

### 2. Configuration des variables d'environnement
```bash
# Variables Symfony
heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=false
heroku config:set APP_SECRET=$(openssl rand -hex 32)

# Configuration du mailer (optionnel)
heroku config:set MAILER_DSN=smtp://username:password@smtp.sendgrid.net:587
```

### 3. Ajout des add-ons
```bash
# MySQL
heroku addons:create jawsdb:kitefin

# MongoDB
heroku addons:create mongolab:sandbox

# Vérifier les add-ons installés
heroku addons
```

### 4. Vérifier les variables d'environnement
```bash
# Voir toutes les variables
heroku config

# Les variables importantes qui doivent être présentes :
# - APP_ENV=prod
# - APP_SECRET=...
# - JAWSDB_URL=mysql://... (ou CLEARDB_DATABASE_URL)
# - MONGODB_URI=mongodb://... (ou MONGOLAB_URI)
```

### 5. Déploiement
```bash
# Ajouter les fichiers au Git
git add .
git commit -m "Configuration pour Heroku"

# Déployer
git push heroku main

# Ou si vous êtes sur une autre branche
git push heroku votre-branche:main
```

### 6. Exécuter les migrations
```bash
# Les migrations s'exécutent automatiquement grâce au Procfile
# Mais vous pouvez aussi les lancer manuellement :
heroku run php bin/console doctrine:migrations:migrate --no-interaction
```

### 7. Vérifier le déploiement
```bash
# Ouvrir l'application
heroku open

# Voir les logs
heroku logs --tail

# Vérifier les processus
heroku ps
```

## Problèmes courants et solutions

### 1. Erreur de mémoire PHP
```bash
# Augmenter la limite de mémoire
heroku config:set PHP_MEMORY_LIMIT=512M
```

### 2. Problème de connexion MongoDB
```bash
# Vérifier que la variable MONGODB_URI est définie
heroku config:get MONGODB_URI

# Vérifier les logs
heroku logs --tail | grep mongodb
```

### 3. Problème de fichiers uploadés
Les fichiers uploadés sur Heroku sont temporaires. Pour une solution permanente :
- Utiliser Amazon S3
- Ou adapter le code pour le stockage local temporaire

### 4. Problème de timezone
```bash
heroku config:set TZ=Europe/Paris
```

## Commandes utiles

```bash
# Redémarrer l'application
heroku restart

# Accéder à la console
heroku run bash

# Voir les variables de configuration
heroku config

# Voir les logs en temps réel
heroku logs --tail

# Scaler l'application
heroku ps:scale web=1
```

## Maintenance et monitoring

### Sauvegardes automatiques
Les add-ons JawsDB et MongoDB Atlas proposent des sauvegardes automatiques dans leurs plans payants.

### Monitoring
```bash
# Voir les métriques
heroku logs --tail

# Utiliser des services comme New Relic ou Sentry pour le monitoring avancé
```

## Coûts

- **Dyno web** : Gratuit pendant 550h/mois
- **JawsDB MySQL** : Gratuit jusqu'à 5MB
- **MongoDB Atlas** : Gratuit jusqu'à 512MB
- **Total** : Gratuit pour développement/test

## Mise à jour

```bash
# Pour mettre à jour l'application
git add .
git commit -m "Mise à jour"
git push heroku main
```

## Liens utiles

- [Documentation Heroku PHP](https://devcenter.heroku.com/categories/php)
- [JawsDB Documentation](https://elements.heroku.com/addons/jawsdb)
- [MongoDB Atlas Documentation](https://elements.heroku.com/addons/mongolab)
