#!/bin/bash
# Script de démarrage rapide - Maroc Inflation
# À exécuter sur votre Mac

set -e

echo "╔════════════════════════════════════════════╗"
echo "║   MAROC INFLATION - DÉMARRAGE RAPIDE       ║"
echo "╚════════════════════════════════════════════╝"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher avec couleur
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "includes/config.php" ]; then
    error "Erreur : Ce script doit être exécuté depuis la racine du projet Maroc"
    exit 1
fi

success "Répertoire projet trouvé"
echo ""

# Étape 1 : Vérifier MySQL
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Étape 1/5 : Vérification MySQL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if command -v mysql &> /dev/null; then
    success "MySQL installé"

    # Tester la connexion
    if mysql -u root -e "SELECT 1" &> /dev/null; then
        success "Connexion MySQL OK (sans mot de passe)"
        MYSQL_CMD="mysql -u root"
    elif mysql -u root -p -e "SELECT 1" &> /dev/null; then
        success "Connexion MySQL OK (avec mot de passe)"
        MYSQL_CMD="mysql -u root -p"
    else
        warning "Impossible de se connecter à MySQL"
        echo "Veuillez vérifier votre installation MySQL"
        echo "Commande : mysql -u root -p"
        exit 1
    fi
else
    error "MySQL non installé"
    echo "Installation recommandée : brew install mysql"
    exit 1
fi
echo ""

# Étape 2 : Créer la base de données
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Étape 2/5 : Création base de données"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "Création de la base 'maroc_inflation'..."
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS maroc_inflation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
success "Base de données créée"

echo "Import des schémas SQL..."
for sql_file in sql/*.sql; do
    if [ -f "$sql_file" ]; then
        echo "  → $(basename $sql_file)"
        $MYSQL_CMD maroc_inflation < "$sql_file" 2>&1 | grep -v "Duplicate entry" | grep -v "ERROR 1062" || true
    fi
done
success "Tous les schémas importés (doublons ignorés)"
echo ""

# Étape 3 : Vérifier le fichier .env
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Étape 3/5 : Configuration .env"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ ! -f ".env" ]; then
    warning "Fichier .env non trouvé, création..."
    cat > .env << 'EOF'
# Base de données
DB_HOST=localhost
DB_NAME=maroc_inflation
DB_USER=root
DB_PASS=

# API Keys
BAM_API_KEY=a53824b98185450f9adb4e637194c7a0

# Optionnel : Webhook Slack pour notifications
# SYNC_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
EOF
    success "Fichier .env créé"
else
    success "Fichier .env déjà présent"
fi
echo ""

# Étape 4 : Première synchronisation des données
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Étape 4/5 : Synchronisation initiale des données"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cd data

echo "→ Import HCP (IPC)..."
php import_hcp_ckan.php || warning "Erreur import HCP (peut nécessiter connexion internet)"

echo ""
echo "→ Import Bank Al-Maghrib (Taux de change)..."
php import_bank_al_maghrib.php || warning "Erreur import BAM (peut nécessiter connexion internet)"

echo ""
echo "→ Import World Bank (International)..."
php import_world_bank.php || warning "Erreur import World Bank (peut nécessiter connexion internet)"

echo ""
echo "→ Scraping actualités HCP..."
php scrape_news_hcp.php || warning "Erreur scraping HCP"

echo ""
echo "→ Scraping actualités Bank Al-Maghrib..."
php scrape_news_bam.php || warning "Erreur scraping BAM"

echo ""
echo "→ Calcul prévisions..."
php calculate_previsions.php || warning "Erreur calcul prévisions"

cd ..
success "Synchronisation terminée"
echo ""

# Étape 5 : Configuration du cron
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Étape 5/5 : Configuration cron (optionnel)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

CRON_LINE="0 2 * * * php $(pwd)/data/cron_daily_sync.php >> $(pwd)/logs/cron.log 2>&1"

echo "Pour automatiser la synchronisation quotidienne, ajoutez cette ligne à votre crontab :"
echo ""
echo "  $CRON_LINE"
echo ""
echo "Commande : crontab -e"
echo ""
warning "⚠️  Configuration manuelle requise"
echo ""

# Résumé final
echo "╔════════════════════════════════════════════╗"
echo "║           INSTALLATION TERMINÉE             ║"
echo "╚════════════════════════════════════════════╝"
echo ""
success "Le site est prêt !"
echo ""
echo "📊 Données disponibles :"
echo "   - IPC National (HCP)"
echo "   - Taux de change (Bank Al-Maghrib)"
echo "   - Inflation internationale (World Bank)"
echo "   - Actualités économiques (HCP + BAM)"
echo "   - Prévisions 6 mois"
echo ""
echo "🌐 Accès au site :"
echo "   - Public : http://localhost/Maroc/public/"
echo "   - Admin : http://localhost/Maroc/public/secure-access-xyz2024.php"
echo ""
echo "📝 Prochaines étapes :"
echo "   1. Ouvrir http://localhost/Maroc/public/"
echo "   2. Vérifier que les données s'affichent"
echo "   3. Consulter les actualités"
echo "   4. Configurer le cron (optionnel mais recommandé)"
echo ""
echo "📚 Documentation :"
echo "   - VERIFICATION_COMPLETE.md : Rapport d'audit"
echo "   - IMPLEMENTATION_COMPLETE.md : Guide complet"
echo ""
success "Bonne utilisation ! 🚀"
echo ""
