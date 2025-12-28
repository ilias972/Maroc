# 🚀 PHASE 1 - Installation Finale sur Votre Machine

**Date :** 28 décembre 2025
**Ce qui a été préparé dans le sandbox :** ✅ Composer, .env, répertoires

---

## ⚠️ IMPORTANT : À exécuter sur VOTRE machine (pas dans le sandbox)

Le sandbox a des restrictions de sécurité qui empêchent la configuration de MariaDB.
**Suivez ces étapes sur votre machine locale** pour terminer l'installation.

---

## 📝 Étape 1 : Création de la base de données

Exécutez cette commande **dans le répertoire du projet** :

```bash
sudo mariadb -u root <<EOF
-- Création de la base
CREATE DATABASE IF NOT EXISTS maroc_inflation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Création d'un utilisateur spécifique (plus sûr que root)
CREATE USER IF NOT EXISTS 'maroc_user'@'localhost' IDENTIFIED BY 'maroc123';

-- Donner les droits
GRANT ALL PRIVILEGES ON maroc_inflation.* TO 'maroc_user'@'localhost';
FLUSH PRIVILEGES;

-- Vérification
SHOW DATABASES LIKE 'maroc_inflation';
SELECT User, Host FROM mysql.user WHERE User='maroc_user';
EOF
```

**Résultat attendu :**
```
+--------------------+
| Database           |
+--------------------+
| maroc_inflation    |
+--------------------+

+-------------+-----------+
| User        | Host      |
+-------------+-----------+
| maroc_user  | localhost |
+-------------+-----------+
```

---

## 📝 Étape 2 : Importation de la structure SQL

```bash
# Méthode 1 (recommandée) :
sudo mariadb maroc_inflation < sql/database.sql

# Si la méthode 1 échoue, essayez :
mariadb -u maroc_user -pmaroc123 maroc_inflation < sql/database.sql
```

**Vérification :**
```bash
mariadb -u maroc_user -pmaroc123 -e "USE maroc_inflation; SHOW TABLES;"
```

**Résultat attendu :** Liste de 11+ tables :
- `ipc_mensuel`
- `inflation_categories`
- `ipc_villes`
- `demographie_villes`
- `inflation_internationale`
- `taux_change`
- `previsions_inflation`
- etc.

---

## 📝 Étape 3 : Vérification finale

### Test 1 : Connexion à la base

```bash
CHECK_DB=1 php tests/smoke.php
```

**Résultat attendu :**
```
✅ Répertoire public présent
✅ Répertoire includes présent
✅ Répertoire data présent
✅ Chargement des fonctions utilitaires
✅ Chargement auth/2FA
✅ Connexion MySQL
Tests réussis. Skipped: 0
```

### Test 2 : Démarrer le serveur

```bash
php -S localhost:8000 -t public
```

Ouvrez votre navigateur : **http://localhost:8000**

**Page d'accueil devrait s'afficher** (avec erreurs normales car pas de données importées)

---

## 🔐 Sécurité : Changer le mot de passe (recommandé)

Si vous voulez un mot de passe différent :

```bash
# 1. Changer dans MariaDB
sudo mariadb -e "ALTER USER 'maroc_user'@'localhost' IDENTIFIED BY 'votre_nouveau_mdp';"

# 2. Mettre à jour .env
nano .env
# Modifier : DB_PASS=votre_nouveau_mdp
```

---

## 🐛 Dépannage

### Erreur : "Access denied for user 'maroc_user'"
```bash
# Vérifier que l'utilisateur existe
sudo mariadb -e "SELECT User, Host FROM mysql.user WHERE User='maroc_user';"

# Si absent, recréer
sudo mariadb <<< "CREATE USER 'maroc_user'@'localhost' IDENTIFIED BY 'maroc123'; GRANT ALL ON maroc_inflation.* TO 'maroc_user'@'localhost'; FLUSH PRIVILEGES;"
```

### Erreur : "Unknown database 'maroc_inflation'"
```bash
# Vérifier que la base existe
sudo mariadb -e "SHOW DATABASES LIKE 'maroc%';"

# Si absente, recréer
sudo mariadb -e "CREATE DATABASE maroc_inflation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Erreur : "Can't connect to local MySQL server"
```bash
# Vérifier que MariaDB tourne
sudo systemctl status mariadb

# Si arrêté, démarrer
sudo systemctl start mariadb
```

---

## ✅ Checklist finale

- [ ] Base de données `maroc_inflation` créée
- [ ] Utilisateur `maroc_user` créé avec mot de passe
- [ ] Structure SQL importée (11 tables visibles)
- [ ] Fichier `.env` configuré
- [ ] Test `CHECK_DB=1 php tests/smoke.php` passe ✅
- [ ] Serveur `php -S localhost:8000 -t public` démarre
- [ ] Page http://localhost:8000 s'affiche

---

## 🎯 Prochaines étapes (Phase 2)

Une fois que tout fonctionne :

1. **Importer les données réelles :**
   ```bash
   php data/import_hcp_ckan.php
   php data/import_bank_al_maghrib.php
   php data/import_world_bank.php
   php data/calculate_previsions.php
   ```

2. **Configurer le cron quotidien :**
   ```bash
   crontab -e
   # Ajouter :
   0 2 * * * cd /chemin/vers/Maroc && php data/cron_daily_sync.php
   ```

3. **Sécuriser l'API Bank Al-Maghrib :**
   - Déplacer la clé API dans `.env`
   - Voir le rapport d'audit pour les détails

---

**🎉 Bon courage ! N'hésitez pas si vous rencontrez un problème.**
