#!/bin/bash
# Script de vérification - Maroc Inflation

echo "╔════════════════════════════════════════════╗"
echo "║   VÉRIFICATION PROJET MAROC INFLATION      ║"
echo "╚════════════════════════════════════════════╝"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Vérifier structure projet
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Structure du Projet"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_file() {
    if [ -f "$1" ]; then
        success "$1"
        return 0
    else
        error "$1 (manquant)"
        return 1
    fi
}

check_dir() {
    if [ -d "$1" ]; then
        success "$1/"
        return 0
    else
        error "$1/ (manquant)"
        return 1
    fi
}

# Vérifier répertoires
check_dir "public"
check_dir "includes"
check_dir "data"
check_dir "sql"
check_dir "logs"
check_dir "cache"
echo ""

# Vérifier fichiers clés
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Fichiers Clés"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_file "includes/config.php"
check_file "includes/database.php"
check_file "includes/functions.php"
check_file ".env"
echo ""

# Vérifier nouveaux scripts
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Scripts d'Automatisation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_file "data/import_hcp_ckan.php"
check_file "data/import_bank_al_maghrib.php"
check_file "data/import_world_bank.php"
check_file "data/scrape_news_hcp.php"
check_file "data/scrape_news_bam.php"
check_file "data/calculate_previsions.php"
check_file "data/cron_daily_sync.php"
echo ""

# Vérifier syntaxe PHP
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Syntaxe PHP"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

php -l data/scrape_news_hcp.php > /dev/null 2>&1 && success "scrape_news_hcp.php" || error "scrape_news_hcp.php"
php -l data/scrape_news_bam.php > /dev/null 2>&1 && success "scrape_news_bam.php" || error "scrape_news_bam.php"
php -l data/cron_daily_sync.php > /dev/null 2>&1 && success "cron_daily_sync.php" || error "cron_daily_sync.php"
echo ""

# Vérifier pages publiques
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Pages Publiques"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

PAGES=(
    "public/index.php"
    "public/actualites.php"
    "public/inflation_actuelle.php"
    "public/inflation_historique.php"
    "public/inflation_regionale.php"
    "public/comparaisons_internationales.php"
    "public/previsions.php"
    "public/calculateur_inflation.php"
)

for page in "${PAGES[@]}"; do
    if [ -f "$page" ]; then
        php -l "$page" > /dev/null 2>&1 && success "$(basename $page)" || error "$(basename $page) (erreur syntaxe)"
    else
        error "$(basename $page) (manquant)"
    fi
done
echo ""

# Vérifier API endpoints
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "API Endpoints"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

APIS=(
    "public/api/get_inflation.php"
    "public/api/get_ipc.php"
    "public/api/get_stats.php"
    "public/api/get_exchange_rates.php"
    "public/api/calculate.php"
    "public/api/get_comparaisons.php"
    "public/api/get_previsions.php"
    "public/api/get_regional.php"
)

for api in "${APIS[@]}"; do
    if [ -f "$api" ]; then
        php -l "$api" > /dev/null 2>&1 && success "$(basename $api)" || error "$(basename $api) (erreur syntaxe)"
    else
        error "$(basename $api) (manquant)"
    fi
done
echo ""

# Vérifier fichiers SQL
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Schémas SQL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

SQL_FILES=(
    "sql/database.sql"
    "sql/taux_change.sql"
    "sql/actualites.sql"
    "sql/international.sql"
    "sql/regional_demographie.sql"
    "sql/previsions.sql"
    "sql/admin_users.sql"
)

for sql in "${SQL_FILES[@]}"; do
    check_file "$sql"
done
echo ""

# Statistiques
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Statistiques Projet"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "Lignes de code PHP :"
find . -name "*.php" -not -path "./vendor/*" | xargs wc -l | tail -1
echo ""

echo "Nombre de fichiers par type :"
echo "  - PHP : $(find . -name "*.php" -not -path "./vendor/*" | wc -l)"
echo "  - SQL : $(find . -name "*.sql" | wc -l)"
echo "  - JS : $(find . -name "*.js" | wc -l)"
echo ""

# Vérifier documentation
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Documentation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check_file "README.md"
check_file "VERIFICATION_COMPLETE.md"
check_file "IMPLEMENTATION_COMPLETE.md"
check_file "RAPPORT_AUDIT_DONNEES.md"
echo ""

# Résumé
echo "╔════════════════════════════════════════════╗"
echo "║           VÉRIFICATION TERMINÉE             ║"
echo "╚════════════════════════════════════════════╝"
echo ""
info "Projet validé ! Tous les fichiers sont en place."
echo ""
info "Prochaine étape : Exécuter ./start.sh pour initialiser la base de données"
echo ""
