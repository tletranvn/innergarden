#!/bin/bash
# Script de vérification avant déploiement Heroku

echo "🔍 Vérification de la préparation pour Heroku..."

# Vérifier les fichiers requis
echo "Vérification des fichiers requis..."
files_required=("Procfile" "composer.json" "app.json" ".env.prod")
for file in "${files_required[@]}"; do
    if [ -f "$file" ]; then
        echo "  $file existe"
    else
        echo "  $file manquant"
    fi
done

# Vérifier les dépendances Composer
echo "Vérification des dépendances..."
if [ -f "composer.lock" ]; then
    echo "  composer.lock existe"
else
    echo "  composer.lock manquant - exécuter 'composer install'"
fi

# Vérifier la configuration Doctrine
echo "Vérification de la configuration Doctrine..."
if grep -q "doctrine" composer.json; then
    echo "  Doctrine configuré"
else
    echo "  Doctrine non configuré"
fi

# Vérifier les migrations
echo "Vérification des migrations..."
if [ -d "migrations" ] && [ "$(ls -A migrations)" ]; then
    echo "  Migrations présentes"
else
    echo "  Aucune migration trouvée"
fi

# Vérifier la configuration MongoDB
echo "Vérification de la configuration MongoDB..."
if grep -q "mongodb" composer.json; then
    echo "MongoDB ODM configuré"
else
    echo "MongoDB ODM non configuré"
fi

# Vérifier les variables d'environnement
echo "Vérification des variables d'environnement..."
if [ -f ".env.prod" ]; then
    echo ".env.prod existe"
    
    # Vérifier les variables importantes
    important_vars=("APP_ENV" "APP_DEBUG" "APP_SECRET")
    for var in "${important_vars[@]}"; do
        if grep -q "$var" .env.prod; then
            echo "$var défini"
        else
            echo "$var manquant"
        fi
    done
else
    echo ".env.prod manquant"
fi

# Vérifier Git
echo "Vérification Git..."
if [ -d ".git" ]; then
    echo "Dépôt Git initialisé"
    
    # Vérifier les fichiers non committés
    if [ -n "$(git status --porcelain)" ]; then
        echo "Fichiers non committés détectés"
        git status --short
    else
        echo "Tous les fichiers sont committés"
    fi
else
    echo "Dépôt Git non initialisé"
fi

echo ""
echo "Prochaines étapes recommandées :"
echo "1. Installer Heroku CLI : brew install heroku/brew/heroku"
echo "2. Se connecter : heroku login"
echo "3. Créer l'app : heroku create votre-nom-app"
echo "4. Ajouter les add-ons : heroku addons:create jawsdb:kitefin"
echo "5. Déployer : git push heroku main"
echo ""
echo "Consulter HEROKU_DEPLOY_GUIDE.md pour plus de détails"
