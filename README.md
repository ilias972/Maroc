# 🇲🇦 Maroc Inflation - Site Officiel

## 📊 Présentation

Site web professionnel d'analyse de l'inflation au Maroc avec données 100% officielles.

### Sources de données :
- **HCP** (Haut-Commissariat au Plan) - IPC mensuel
- **Bank Al-Maghrib** - Taux de change quotidiens
- **World Bank** - Comparaisons internationales

## 🎯 Fonctionnalités

### Pages Publiques (13)
- Accueil avec indicateurs clés
- Inflation actuelle (mois courant)
- Historique complet (2007-2025)
- Inflation régionale (17 villes)
- Comparaisons internationales (8 pays)
- Calculateur d'inflation
- Prévisions 6 mois
- Actualités économiques
- **Graphiques avancés (4 types)**
- Exports PDF/Excel

### Interface Admin (9)
- Dashboard statistiques
- Synchronisation données manuelle
- Gestion actualités (CRUD)
- Gestion utilisateurs
- Logs système
- Cache système
- Paramètres

### APIs REST (8)
- `/api/get_inflation.php`
- `/api/get_ipc.php`
- `/api/get_comparaisons.php`
- `/api/get_previsions.php`
- `/api/get_regional.php`
- `/api/get_stats.php`
- `/api/get_exchange_rates.php`
- `/api/calculate.php`

## 🚀 Installation

### Prérequis
- PHP 8.2+
- MySQL 9.5+
- Composer

### Configuration

1. Cloner le projet
2. Installer dépendances : `composer install`
3. Importer la base : `mysql < sql/database.sql`
4. Configurer `.env`
5. Démarrer serveur : `php -S localhost:8000 -t public`

### Première synchronisation
```bash
php data/cron_daily_sync.php
```

### Tests & CI
- Lint + smoke tests : `bash tests/run.sh` (définir `CHECK_DB=1` pour tester la connexion MySQL)
- CI GitHub Actions : `.github/workflows/ci.yml`

### Exports & données
- Page publique d'accès aux exports : `public/exports.php`
- Exports directs : `export_historique.php`, `export_regional.php`, `export_comparaisons.php`
- Plan du site : `sitemap.xml.php`

### Synchronisation
- Cron recommandé : `0 2 * * * php data/cron_daily_sync.php`
- Log des runs : `logs/sync.log`
- Alertes échec : définir `SYNC_WEBHOOK_URL` (Slack/webhook HTTP)
- Détails : `docs/SYNC.md`

## 📈 Statistiques

- **31 pages** totales
- **100% fonctionnelles**
- **0 erreur fatale**
- **3 sources officielles**
- **228 mois de données IPC**
- **8 pays comparés**
- **2 langues (FR/EN)**

## 🔐 Sécurité

- Authentification 2FA
- URLs admin obscurcies
- Rate limiting
- Protection CSRF
- Mots de passe hashés (bcrypt)

## 📊 Technologies

- **Backend :** PHP 8.2, MySQL
- **Frontend :** Bootstrap 5, Chart.js
- **Bibliothèques :** PhpSpreadsheet, mPDF, Guzzle

## 📞 Contact

Site : https://maroc-inflation.ma (à déployer)

---

**© 2025 Maroc Inflation - Tous droits réservés**
