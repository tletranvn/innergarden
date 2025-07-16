#!/bin/bash
# Script de post-déploiement pour Heroku

echo "Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Création du dossier uploads temporaire..."
mkdir -p /tmp/uploads
chmod 777 /tmp/uploads

echo "Chargement des fixtures (optionnel pour la production)..."
# php bin/console doctrine:fixtures:load --no-interaction

echo "Déploiement terminé !"
