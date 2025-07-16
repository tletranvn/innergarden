#!/bin/bash
# Guide de configuration MongoDB Atlas pour Heroku

echo "GUIDE DE CONFIGURATION MONGODB ATLAS"
echo "======================================="
echo ""
echo "1. Créer un compte MongoDB Atlas"
echo "   - Aller sur: https://www.mongodb.com/cloud/atlas"
echo "   - Cliquer sur 'Try Free'"
echo "   - Créer un compte gratuit"
echo ""
echo "2. Créer un cluster gratuit"
echo "   - Choisir 'M0 Sandbox' (gratuit)"
echo "   - Région: choisir une région proche (ex: Paris eu-west-3)"
echo "   - Nom du cluster: 'Cluster0'"
echo ""
echo "3. Créer un utilisateur de base de données"
echo "   - Username: innergarden_user"
echo "   - Password: (générer un mot de passe fort)"
echo "   - Copier le mot de passe quelque part de sûr"
echo ""
echo "4. Configurer l'accès réseau"
echo "   - Cliquer sur 'Network Access'"
echo "   - Ajouter '0.0.0.0/0' pour permettre l'accès depuis Heroku"
echo "   - (En production, restreindre aux IPs Heroku)"
echo ""
echo "5. Obtenir l'URI de connexion"
echo "   - Cliquer sur 'Connect' sur votre cluster"
echo "   - Choisir 'Connect your application'"
echo "   - Copier l'URI (format: mongodb+srv://...)"
echo ""
echo "6. L'URI ressemble à:"
echo "   mongodb+srv://innergarden_user:PASSWORD@innergarden-cluster.xxxxx.mongodb.net/innergarden?retryWrites=true&w=majority"
echo ""
echo "7. Configurer sur Heroku"
echo "   heroku config:set MONGODB_URL='votre-uri-mongodb'"
echo ""
echo "Une fois terminé, appuyez sur Entrée pour continuer..."
read -p ""

echo ""
echo "Prêt à configurer l'URI MongoDB sur Heroku ? (y/n)"
read -p "Réponse: " ready

if [ "$ready" = "y" ] || [ "$ready" = "Y" ]; then
    echo ""
    echo "Entrez votre URI MongoDB Atlas:"
    read -p "URI: " mongodb_uri
    
    if [ -n "$mongodb_uri" ]; then
        echo "Configuration de l'URI MongoDB sur Heroku..."
        heroku config:set MONGODB_URL="$mongodb_uri"
        echo "URI MongoDB configurée!"
        
        echo ""
        echo "🚀 Redéploiement avec MongoDB configuré..."
        git add .
        git commit -m "Configuration MongoDB Atlas"
        git push heroku main
    else
        echo "URI MongoDB vide. Configuration annulée."
    fi
else
    echo "Configuration manuelle requise:"
    echo "heroku config:set MONGODB_URL='votre-uri-mongodb-atlas'"
fi
