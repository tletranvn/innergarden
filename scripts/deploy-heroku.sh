#!/bin/bash
# Script de déploiement automatique sur Heroku

echo "Déploiement sur Heroku"
echo "========================"

# Vérifier si Heroku CLI est installé
if ! command -v heroku &> /dev/null; then
    echo "Heroku CLI n'est pas installé"
    echo "Installez-le avec: brew install heroku/brew/heroku"
    exit 1
fi

# Vérifier si l'utilisateur est connecté à Heroku
if ! heroku auth:whoami &> /dev/null; then
    echo "Vous n'êtes pas connecté à Heroku"
    echo "Connectez-vous avec: heroku login"
    exit 1
fi

# Demander le nom de l'application
read -p "Nom de l'application Heroku (laissez vide pour un nom automatique): " app_name

# Créer l'application
if [ -z "$app_name" ]; then
    echo "Création de l'application avec un nom automatique..."
    heroku create
else
    echo "Création de l'application '$app_name'..."
    heroku create $app_name
fi

# Configuration des variables d'environnement
echo "Configuration des variables d'environnement..."
heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=false
heroku config:set APP_SECRET=$(openssl rand -hex 32)

# Ajout des addons pour MySQL et MongoDB
echo "Ajout des addons..."
heroku addons:create jawsdb:kitefin || echo "JawsDB déjà installé"
heroku addons:create mongolab:sandbox || echo "MongoDB Atlas déjà installé"

# Déploiement
echo "Déploiement de l'application..."
git add .
git commit -m "Déploiement initial sur Heroku"
git push heroku main

# Post-déploiement
echo "Exécution des migrations..."
heroku run php bin/console doctrine:migrations:migrate --no-interaction

# Ouverture de l'application
echo "Déploiement terminé !"
echo "Ouverture de l'application..."
heroku open

echo ""
echo "Informations utiles:"
echo "- Logs: heroku logs --tail"
echo "- Console: heroku run bash"
echo "- Redémarrage: heroku restart"
