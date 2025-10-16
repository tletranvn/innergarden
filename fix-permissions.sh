#!/bin/bash

# Script pour corriger les permissions des fichiers créés par Docker
# À exécuter avec: ./fix-permissions.sh

echo "════════════════════════════════════════════════════════════════"
echo "  🔧 CORRECTION DES PERMISSIONS"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}📋 Fichiers à corriger:${NC}"
echo "  • src/Controller/Admin/"
echo "  • src/Form/UserEditType.php"
echo "  • templates/admin/users/"
echo "  • templates/partials/_breadcrumb.html.twig"
echo "  • templates/form/accessible_form_theme.html.twig"
echo "  • templates/privacy/"
echo "  • scripts/optimize-images.sh"
echo "  • Documentation (*.md)"
echo ""

echo -e "${YELLOW}⚠️  Ce script nécessite sudo pour corriger les permissions${NC}"
echo -e "${YELLOW}   Votre mot de passe vous sera demandé${NC}"
echo ""

read -p "Continuer? (o/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Oo]$ ]]
then
    echo "Annulé."
    exit 1
fi

echo ""
echo -e "${BLUE}🔧 Correction des permissions en cours...${NC}"
echo ""

# Fonction pour corriger les permissions
fix_permissions() {
    local path=$1
    if [ -e "$path" ]; then
        echo "  ✓ $path"
        sudo chown -R $USER:$USER "$path"
    else
        echo "  ⊘ $path (n'existe pas)"
    fi
}

# Corriger tous les fichiers/dossiers créés
fix_permissions "src/Controller/Admin"
fix_permissions "src/Form/UserEditType.php"
fix_permissions "templates/admin/users"
fix_permissions "templates/partials/_breadcrumb.html.twig"
fix_permissions "templates/form/accessible_form_theme.html.twig"
fix_permissions "templates/privacy"
fix_permissions "src/Controller/PrivacyController.php"
fix_permissions "scripts/optimize-images.sh"
fix_permissions "deploy-heroku.sh"

# Documentation
fix_permissions "ACCESSIBILITY.md"
fix_permissions "ECO-CONCEPTION.md"
fix_permissions "ECO-QUICK-WINS.md"
fix_permissions "ECO-README.md"
fix_permissions "ECO-SPRINT1-SUMMARY.md"
fix_permissions "ECO-TEST-RESULTS.md"
fix_permissions "TEST-RESULTS.md"
fix_permissions "DEPLOY-GUIDE.md"
fix_permissions "USER-MANAGEMENT-FEATURE.md"

echo ""
echo -e "${GREEN}✅ Permissions corrigées avec succès!${NC}"
echo ""
echo -e "${BLUE}📊 Vérification:${NC}"
ls -lah src/Controller/Admin/ 2>/dev/null | grep -v "^d" | awk '{print "  " $3":"$4 " - " $9}'
echo ""
echo "════════════════════════════════════════════════════════════════"
