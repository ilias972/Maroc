# 🚀 DÉMARRAGE RAPIDE - MAROC INFLATION

## En 3 commandes sur votre Mac

### 1️⃣ Vérifier que tout est en place
```bash
cd /path/to/Maroc
./verify.sh
```

**Résultat attendu :** Tous les ✅ verts

---

### 2️⃣ Initialiser la base de données et synchroniser
```bash
./start.sh
```

**Ce script fait automatiquement :**
- ✅ Vérifie MySQL
- ✅ Crée la base `maroc_inflation`
- ✅ Importe tous les schémas SQL
- ✅ Lance la première synchronisation (HCP, BAM, World Bank)
- ✅ Scrape les actualités HCP et Bank Al-Maghrib
- ✅ Calcule les prévisions

⏱️ **Durée :** 2-5 minutes (selon connexion internet)

---

### 3️⃣ Ouvrir le site
```bash
open http://localhost/Maroc/public/
```

**Pages disponibles :**
- 🏠 Accueil : http://localhost/Maroc/public/
- 📰 Actualités : http://localhost/Maroc/public/actualites.php
- 📊 Inflation actuelle : http://localhost/Maroc/public/inflation_actuelle.php
- 📈 Historique : http://localhost/Maroc/public/inflation_historique.php
- 🗺️ Régional : http://localhost/Maroc/public/inflation_regionale.php
- 🌍 International : http://localhost/Maroc/public/comparaisons_internationales.php
- 🔮 Prévisions : http://localhost/Maroc/public/previsions.php
- 🧮 Calculateur : http://localhost/Maroc/public/calculateur_inflation.php

---

## ⚙️ Configuration cron (automatisation)

Pour que les données se mettent à jour automatiquement chaque jour :

```bash
crontab -e
```

Ajouter cette ligne :
```
0 2 * * * php /path/to/Maroc/data/cron_daily_sync.php >> /path/to/Maroc/logs/cron.log 2>&1
```

*(Remplacer `/path/to` par le chemin réel)*

---

## 🧪 Tester un scraper manuellement

```bash
cd data

# Scraper HCP
php scrape_news_hcp.php

# Scraper Bank Al-Maghrib
php scrape_news_bam.php

# Import HCP
php import_hcp_ckan.php

# Import BAM
php import_bank_al_maghrib.php

# Import World Bank
php import_world_bank.php

# Calcul prévisions
php calculate_previsions.php
```

---

## 📊 Vérifier les données en base

```bash
mysql -u root maroc_inflation -e "SELECT COUNT(*) FROM ipc_mensuel"
mysql -u root maroc_inflation -e "SELECT COUNT(*) FROM actualites_economiques"
mysql -u root maroc_inflation -e "SELECT COUNT(*) FROM taux_change"
mysql -u root maroc_inflation -e "SELECT COUNT(*) FROM inflation_internationale"
mysql -u root maroc_inflation -e "SELECT COUNT(*) FROM previsions_inflation"
```

---

## 📝 Logs

```bash
# Logs de synchronisation quotidienne
tail -f logs/sync.log

# Logs d'erreurs PHP
tail -f logs/error.log

# Logs cron (après activation)
tail -f logs/cron.log
```

---

## 🔧 Résolution de problèmes

### "Erreur connexion base de données"
```bash
# Vérifier que MySQL tourne
mysql.server status

# Démarrer MySQL si nécessaire
mysql.server start

# Tester connexion
mysql -u root -p -e "SELECT 1"
```

### "No such file or directory"
Vérifier que vous êtes dans le bon répertoire :
```bash
pwd
# Doit afficher : /path/to/Maroc
```

### "Permission denied"
Rendre les scripts exécutables :
```bash
chmod +x start.sh verify.sh
```

### "Class 'Database' not found"
Vérifier le fichier .env :
```bash
cat .env
# Doit contenir :
# DB_HOST=localhost
# DB_NAME=maroc_inflation
# DB_USER=root
# DB_PASS=
```

---

## 📚 Documentation complète

- **VERIFICATION_COMPLETE.md** : Rapport d'audit détaillé
- **IMPLEMENTATION_COMPLETE.md** : Guide complet d'implémentation
- **README.md** : Documentation projet

---

## ✅ Checklist avant production

- [ ] MySQL configuré
- [ ] Base de données créée (`maroc_inflation`)
- [ ] Première synchronisation effectuée (./start.sh)
- [ ] Actualités visibles sur http://localhost/Maroc/public/actualites.php
- [ ] Graphiques affichent des données
- [ ] APIs retournent du JSON : http://localhost/Maroc/public/api/get_inflation.php
- [ ] Cron configuré (optionnel mais recommandé)
- [ ] Logs écrits dans `logs/sync.log`

---

## 🎯 Résultat attendu

Après ces 3 étapes, votre site **Maroc Inflation** sera :

- ✅ **100% opérationnel** avec données réelles
- ✅ **Actualités automatiques** (HCP + Bank Al-Maghrib)
- ✅ **Graphiques interactifs** avec Chart.js
- ✅ **APIs REST** fonctionnelles
- ✅ **Prévisions à 6 mois** calculées
- ✅ **Zéro donnée mockée** - Tout vient de sources officielles

---

**Durée totale : 5-10 minutes** ⏱️

**Bonne utilisation ! 🚀**
