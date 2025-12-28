#!/bin/bash
# ============================================================================
# Script d'installation automatique MySQL via Docker
# Projet: Maroc Inflation
# ============================================================================

set -e  # Arrêter en cas d'erreur

# Couleurs pour l'affichage
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════╗"
echo "║   INSTALLATION DOCKER MYSQL                ║"
echo "║   Maroc Inflation Database                 ║"
echo "╚════════════════════════════════════════════╝"
echo -e "${NC}"

# Vérifier que Docker est installé
echo -e "${YELLOW}📋 Vérification de Docker...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker n'est pas installé !${NC}"
    echo ""
    echo "Installez Docker :"
    echo "  - Linux: curl -fsSL https://get.docker.com | sh"
    echo "  - macOS/Windows: https://www.docker.com/products/docker-desktop"
    exit 1
fi

docker --version
echo -e "${GREEN}✅ Docker détecté${NC}"
echo ""

# Arrêter et supprimer l'ancien conteneur s'il existe
echo -e "${YELLOW}🧹 Nettoyage de l'ancien conteneur...${NC}"
docker stop maroc-mysql 2>/dev/null && echo "  → Conteneur arrêté" || true
docker rm maroc-mysql 2>/dev/null && echo "  → Conteneur supprimé" || true
echo ""

# Créer et démarrer le conteneur MySQL
echo -e "${YELLOW}🐳 Création du conteneur MySQL...${NC}"
docker run --name maroc-mysql \
  -e MYSQL_ROOT_PASSWORD=maroc123 \
  -e MYSQL_DATABASE=maroc_inflation \
  -e MYSQL_USER=maroc_user \
  -e MYSQL_PASSWORD=maroc123 \
  -p 3306:3306 \
  -d mysql:8.0 \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_unicode_ci

echo -e "${GREEN}✅ Conteneur créé${NC}"
echo ""

# Vérifier que le conteneur tourne
if docker ps | grep -q maroc-mysql; then
    echo -e "${GREEN}✅ Conteneur en cours d'exécution${NC}"
else
    echo -e "${RED}❌ Le conteneur ne tourne pas !${NC}"
    echo "Logs du conteneur :"
    docker logs maroc-mysql
    exit 1
fi
echo ""

# Attendre que MySQL soit prêt
echo -e "${YELLOW}⏳ Attente du démarrage de MySQL...${NC}"
echo "   (Cela peut prendre 15-30 secondes)"

ATTEMPT=0
MAX_ATTEMPTS=30

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if docker exec maroc-mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo -e "${GREEN}✅ MySQL prêt !${NC}"
        break
    fi

    ATTEMPT=$((ATTEMPT + 1))
    echo -n "."
    sleep 1
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo -e "${RED}❌ MySQL n'a pas démarré dans les temps${NC}"
    echo "Logs :"
    docker logs maroc-mysql | tail -20
    exit 1
fi
echo ""

# Import de la structure SQL
echo -e "${YELLOW}📊 Import de la structure SQL...${NC}"
if [ -f "sql/database.sql" ]; then
    docker exec -i maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation < sql/database.sql
    echo -e "${GREEN}✅ Structure SQL importée${NC}"
else
    echo -e "${RED}❌ Fichier sql/database.sql introuvable !${NC}"
    echo "Assurez-vous d'exécuter ce script depuis la racine du projet."
    exit 1
fi
echo ""

# Vérification des tables
echo -e "${YELLOW}🔍 Vérification des tables...${NC}"
TABLE_COUNT=$(docker exec maroc-mysql mysql -umaroc_user -pmaroc123 -sN -e "USE maroc_inflation; SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='maroc_inflation';")

if [ "$TABLE_COUNT" -ge 11 ]; then
    echo -e "${GREEN}✅ $TABLE_COUNT tables créées${NC}"
    echo ""
    echo "Tables disponibles :"
    docker exec maroc-mysql mysql -umaroc_user -pmaroc123 -e "USE maroc_inflation; SHOW TABLES;"
else
    echo -e "${RED}❌ Seulement $TABLE_COUNT tables trouvées (attendu: 11+)${NC}"
fi
echo ""

# Résumé final
echo -e "${BLUE}"
echo "╔════════════════════════════════════════════╗"
echo "║        INSTALLATION TERMINÉE ! 🎉          ║"
echo "╚════════════════════════════════════════════╝"
echo -e "${NC}"
echo ""
echo -e "${GREEN}📊 Configuration :${NC}"
echo "  • Conteneur    : maroc-mysql"
echo "  • Base         : maroc_inflation"
echo "  • Utilisateur  : maroc_user"
echo "  • Mot de passe : maroc123"
echo "  • Port         : 3306"
echo "  • Tables       : $TABLE_COUNT"
echo ""
echo -e "${GREEN}🚀 Prochaines étapes :${NC}"
echo ""
echo "  1. Tester la connexion :"
echo "     ${BLUE}CHECK_DB=1 php tests/smoke.php${NC}"
echo ""
echo "  2. Démarrer le serveur :"
echo "     ${BLUE}php -S localhost:8000 -t public${NC}"
echo "     Puis ouvrir : http://localhost:8000"
echo ""
echo "  3. Importer les données (optionnel) :"
echo "     ${BLUE}php data/import_hcp_ckan.php${NC}"
echo "     ${BLUE}php data/import_bank_al_maghrib.php${NC}"
echo "     ${BLUE}php data/import_world_bank.php${NC}"
echo ""
echo -e "${GREEN}🔧 Commandes utiles :${NC}"
echo "  • Logs           : docker logs -f maroc-mysql"
echo "  • MySQL shell    : docker exec -it maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation"
echo "  • Arrêter        : docker stop maroc-mysql"
echo "  • Redémarrer     : docker start maroc-mysql"
echo "  • Supprimer      : docker stop maroc-mysql && docker rm maroc-mysql"
echo ""
echo -e "${GREEN}✨ Bon développement !${NC}"
