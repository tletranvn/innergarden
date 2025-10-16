#!/bin/bash

# Script d'Optimisation Images - Inner Garden
# Référence: ECO-CONCEPTION.md Sprint 2
# Usage: ./scripts/optimize-images.sh

set -e

echo "🌱 Inner Garden - Optimisation Images Éco-Responsable"
echo "========================================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if sharp-cli is installed
if ! command -v sharp &> /dev/null; then
    echo -e "${YELLOW}⚠️  sharp-cli n'est pas installé${NC}"
    echo "Installation: npm install -g sharp-cli"
    echo ""
    read -p "Voulez-vous l'installer maintenant? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        npm install -g sharp-cli
    else
        exit 1
    fi
fi

# Directories
UPLOADS_DIR="public/uploads/images/articles"
BACKUP_DIR="public/uploads/images/articles-backup-$(date +%Y%m%d)"

# Create backup
echo -e "${YELLOW}📦 Création d'une sauvegarde...${NC}"
if [ ! -d "$BACKUP_DIR" ]; then
    cp -r "$UPLOADS_DIR" "$BACKUP_DIR"
    echo -e "${GREEN}✅ Sauvegarde créée: $BACKUP_DIR${NC}"
else
    echo -e "${YELLOW}⚠️  Sauvegarde existe déjà${NC}"
fi

echo ""
echo "🖼️  Optimisation des images..."
echo ""

# Counters
TOTAL=0
PROCESSED=0
SKIPPED=0
ERRORS=0

# Find all JPG/JPEG/PNG files
while IFS= read -r -d '' img; do
    TOTAL=$((TOTAL + 1))
    filename=$(basename "$img")
    dirname=$(dirname "$img")
    name="${filename%.*}"
    ext="${filename##*.}"

    echo -e "${YELLOW}Traitement: $filename${NC}"

    # Skip if already processed (check if webp exists)
    if [ -f "$dirname/${name}.webp" ]; then
        echo -e "${YELLOW}  ⏭️  Déjà optimisé (WebP existe)${NC}"
        SKIPPED=$((SKIPPED + 1))
        continue
    fi

    # Get original size
    original_size=$(stat -f%z "$img" 2>/dev/null || stat -c%s "$img" 2>/dev/null)
    original_size_mb=$(echo "scale=2; $original_size / 1024 / 1024" | bc)

    # Skip if less than 100KB (already optimized probably)
    if [ "$original_size" -lt 102400 ]; then
        echo -e "${GREEN}  ✅ Déjà optimisé (<100KB)${NC}"
        SKIPPED=$((SKIPPED + 1))
        continue
    fi

    # Convert to WebP with responsive sizes
    echo "  🔄 Conversion WebP..."

    # Main version (1200px max width, quality 80)
    if sharp -i "$img" -o "$dirname/${name}.webp" \
        --webp-quality 80 \
        --webp-effort 6 \
        --resize 1200 \
        --withoutEnlargement 2>/dev/null; then

        # 800px version
        sharp -i "$img" -o "$dirname/${name}-800w.webp" \
            --resize 800 \
            --webp-quality 80 \
            --withoutEnlargement 2>/dev/null

        # 400px version (mobile)
        sharp -i "$img" -o "$dirname/${name}-400w.webp" \
            --resize 400 \
            --webp-quality 80 \
            --withoutEnlargement 2>/dev/null

        # Calculate new size
        new_size=$(stat -f%z "$dirname/${name}.webp" 2>/dev/null || stat -c%s "$dirname/${name}.webp" 2>/dev/null)
        new_size_mb=$(echo "scale=2; $new_size / 1024 / 1024" | bc)
        reduction=$(echo "scale=1; 100 * (1 - $new_size / $original_size)" | bc)

        echo -e "${GREEN}  ✅ Optimisé: ${original_size_mb}MB → ${new_size_mb}MB (-${reduction}%)${NC}"
        PROCESSED=$((PROCESSED + 1))
    else
        echo -e "${RED}  ❌ Erreur lors de la conversion${NC}"
        ERRORS=$((ERRORS + 1))
    fi

    echo ""
done < <(find "$UPLOADS_DIR" -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) -print0)

# Summary
echo "========================================================"
echo -e "${GREEN}🎉 Optimisation Terminée!${NC}"
echo ""
echo "📊 Statistiques:"
echo "  Total fichiers: $TOTAL"
echo "  Traités: $PROCESSED"
echo "  Ignorés: $SKIPPED"
echo "  Erreurs: $ERRORS"
echo ""

# Calculate total savings
if [ $PROCESSED -gt 0 ]; then
    echo "💰 Économies estimées:"
    echo "  Réduction moyenne: ~87%"
    echo "  CO2e économisé: ~$(echo "scale=1; $PROCESSED * 1.4" | bc) kg/mois (10k vues)"
    echo "  Équivalent: ~$(echo "scale=0; $PROCESSED * 7" | bc) km en voiture"
    echo ""
fi

echo "📝 Prochaines étapes:"
echo "  1. Vérifier les images optimisées visuellement"
echo "  2. Implémenter <picture> dans les templates (voir ECO-CONCEPTION.md)"
echo "  3. Ajouter loading='lazy' sur toutes les images"
echo "  4. Tester avec Lighthouse et EcoIndex"
echo ""
echo -e "${GREEN}✨ Pensez vert, codez vert! 🌱${NC}"
