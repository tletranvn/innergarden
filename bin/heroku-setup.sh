#!/bin/bash
# Script de configuration automatique des variables d'environnement pour Heroku

# Configuration MySQL
if [ -n "$JAWSDB_URL" ]; then
    echo "Configuration JawsDB MySQL détectée"
    export DATABASE_URL="$JAWSDB_URL"
elif [ -n "$CLEARDB_DATABASE_URL" ]; then
    echo "Configuration ClearDB MySQL détectée"
    export DATABASE_URL="$CLEARDB_DATABASE_URL"
else
    echo "Aucune base de données MySQL détectée"
fi

# Configuration MongoDB
if [ -n "$MONGODB_URI" ]; then
    echo "Configuration MongoDB Atlas détectée"
    export MONGODB_URL="$MONGODB_URI"
elif [ -n "$MONGOLAB_URI" ]; then
    echo "Configuration MongoLab détectée"
    export MONGODB_URL="$MONGOLAB_URI"
else
    echo "Aucune base de données MongoDB détectée"
fi

# Afficher les variables configurées
echo "Variables d'environnement configurées :"
echo "- DATABASE_URL: ${DATABASE_URL:0:50}..."
echo "- MONGODB_URL: ${MONGODB_URL:0:50}..."
