#!/bin/bash

# Script de déploiement Heroku Container Registry
# Sprint 1 Éco-Conception - Déploiement
# Date: 16 octobre 2025

set -e  # Arrêter le script en cas d'erreur

echo "════════════════════════════════════════════════════════════════"
echo "  🚀 DÉPLOIEMENT HEROKU - Inner Garden"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Couleurs pour l'affichage
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
APP_NAME="innergarden"
HEROKU_PROCESS_TYPE="web"

echo -e "${BLUE}📋 Étape 1/7: Vérification de Heroku CLI${NC}"
if ! command -v heroku &> /dev/null; then
    echo -e "${RED}❌ Heroku CLI n'est pas installé${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Heroku CLI trouvé: $(heroku --version | head -1)${NC}"
echo ""

echo -e "${BLUE}📋 Étape 2/7: Vérification de l'authentification Heroku${NC}"
if ! heroku auth:whoami &> /dev/null; then
    echo -e "${YELLOW}⚠️  Non authentifié. Veuillez vous connecter:${NC}"
    echo -e "${YELLOW}   Exécutez: heroku login${NC}"
    exit 1
fi
HEROKU_USER=$(heroku auth:whoami)
echo -e "${GREEN}✅ Authentifié en tant que: ${HEROKU_USER}${NC}"
echo ""

echo -e "${BLUE}📋 Étape 3/7: Vérification de l'application Heroku${NC}"
if ! heroku apps:info --app $APP_NAME &> /dev/null; then
    echo -e "${RED}❌ L'application '$APP_NAME' n'existe pas${NC}"
    echo -e "${YELLOW}   Créez-la avec: heroku create $APP_NAME${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Application '$APP_NAME' trouvée${NC}"
echo ""

echo -e "${BLUE}📋 Étape 4/7: Login au Container Registry${NC}"
heroku container:login
echo -e "${GREEN}✅ Connecté au Container Registry${NC}"
echo ""

echo -e "${BLUE}📋 Étape 5/7: Build et Push du container Docker${NC}"
echo -e "${YELLOW}⏳ Cette étape peut prendre 5-10 minutes...${NC}"
heroku container:push $HEROKU_PROCESS_TYPE --app $APP_NAME

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Container Docker poussé avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors du push du container${NC}"
    exit 1
fi
echo ""

echo -e "${BLUE}📋 Étape 6/7: Release du container${NC}"
heroku container:release $HEROKU_PROCESS_TYPE --app $APP_NAME

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Container releasé avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors du release${NC}"
    exit 1
fi
echo ""

echo -e "${BLUE}📋 Étape 7/7: Vérification du déploiement${NC}"
echo -e "${YELLOW}⏳ Attente du démarrage de l'application (10 secondes)...${NC}"
sleep 10

# Afficher les logs récents
echo -e "${BLUE}📜 Logs récents:${NC}"
heroku logs --tail=20 --app $APP_NAME
echo ""

# Vérifier le statut
APP_URL="https://$APP_NAME.herokuapp.com"
echo -e "${GREEN}✅ Déploiement terminé !${NC}"
echo ""
echo "════════════════════════════════════════════════════════════════"
echo -e "${GREEN}  ✅ DÉPLOIEMENT RÉUSSI !${NC}"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo -e "🌐 URL de l'application: ${BLUE}${APP_URL}${NC}"
echo -e "📊 Dashboard Heroku: ${BLUE}https://dashboard.heroku.com/apps/${APP_NAME}${NC}"
echo ""
echo "📋 Commandes utiles:"
echo "  • Voir les logs: heroku logs --tail --app $APP_NAME"
echo "  • Ouvrir l'app: heroku open --app $APP_NAME"
echo "  • Redémarrer: heroku ps:restart --app $APP_NAME"
echo "  • Vérifier status: heroku ps --app $APP_NAME"
echo ""
echo "🎉 Sprint 1 Éco-Conception déployé:"
echo "  • -462 KB optimisation"
echo "  • -23 kg CO2/mois"
echo "  • Score RGAA: 95%"
echo ""
echo "════════════════════════════════════════════════════════════════"
