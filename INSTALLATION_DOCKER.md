# 🐳 INSTALLATION DOCKER MySQL - Alternative Rapide

**Avantage Docker :** Plus rapide, isolation complète, pas besoin de configurer MariaDB système.

---

## ✅ Prérequis

Vérifiez que Docker est installé :

```bash
docker --version
# Résultat attendu : Docker version 20.x ou supérieur
```

**Si Docker n'est pas installé :**
- **Linux :** `curl -fsSL https://get.docker.com | sh`
- **macOS/Windows :** [Télécharger Docker Desktop](https://www.docker.com/products/docker-desktop)

---

## 🚀 Étape 1 : Démarrer le conteneur MySQL

```bash
# Arrêter et supprimer le conteneur s'il existe déjà
docker stop maroc-mysql 2>/dev/null || true
docker rm maroc-mysql 2>/dev/null || true

# Démarrer un nouveau conteneur MySQL
docker run --name maroc-mysql \
  -e MYSQL_ROOT_PASSWORD=maroc123 \
  -e MYSQL_DATABASE=maroc_inflation \
  -e MYSQL_USER=maroc_user \
  -e MYSQL_PASSWORD=maroc123 \
  -p 3306:3306 \
  -d mysql:8.0 \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_unicode_ci

# Vérifier que le conteneur tourne
docker ps | grep maroc-mysql
```

**Résultat attendu :**
```
CONTAINER ID   IMAGE       STATUS         PORTS                    NAMES
abc123def456   mysql:8.0   Up 10 seconds  0.0.0.0:3306->3306/tcp   maroc-mysql
```

---

## ⏳ Étape 2 : Attendre que MySQL soit prêt

MySQL prend quelques secondes pour initialiser. Attendez que le message "ready for connections" apparaisse :

```bash
# Surveiller les logs (Ctrl+C pour arrêter)
docker logs -f maroc-mysql

# OU attendre automatiquement (30 secondes max)
timeout 30 bash -c 'until docker exec maroc-mysql mysqladmin ping -h localhost --silent; do sleep 2; done' && echo "✅ MySQL prêt !"
```

**OU simplement :**
```bash
# Attendre 15 secondes (méthode simple)
sleep 15
```

---

## 📊 Étape 3 : Importer la structure SQL

```bash
# Méthode 1 : Depuis le répertoire du projet
docker exec -i maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation < sql/database.sql

# Méthode 2 : Si vous avez des erreurs de permissions
cat sql/database.sql | docker exec -i maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation

# Méthode 3 : Avec root
docker exec -i maroc-mysql mysql -uroot -pmaroc123 maroc_inflation < sql/database.sql
```

**Vérification :**
```bash
docker exec maroc-mysql mysql -umaroc_user -pmaroc123 -e "USE maroc_inflation; SHOW TABLES;"
```

**Résultat attendu :**
```
+---------------------------+
| Tables_in_maroc_inflation |
+---------------------------+
| admin_users               |
| demographie_villes        |
| inflation_categories      |
| inflation_internationale  |
| ipc_mensuel              |
| ipc_villes               |
| metadata_inflation       |
| panier_ipc               |
| previsions_inflation     |
| site_stats               |
| taux_change              |
+---------------------------+
```

---

## ⚙️ Étape 4 : Vérifier la connexion depuis PHP

```bash
# Tester la connexion avec le script de test
CHECK_DB=1 php tests/smoke.php
```

**Résultat attendu :**
```
✅  Répertoire public présent
✅  Répertoire includes présent
✅  Répertoire data présent
✅  Chargement des fonctions utilitaires
✅  Chargement auth/2FA
✅  Connexion MySQL
Tests réussis. Skipped: 0
```

---

## 🎯 Étape 5 : Démarrer le serveur PHP

```bash
php -S localhost:8000 -t public
```

Ouvrez **http://localhost:8000** dans votre navigateur.

---

## 🔧 Commandes utiles Docker

### Voir les logs MySQL
```bash
docker logs maroc-mysql
docker logs -f maroc-mysql  # Mode suivi en direct
```

### Se connecter au shell MySQL
```bash
# Client MySQL interactif
docker exec -it maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation

# OU avec root
docker exec -it maroc-mysql mysql -uroot -pmaroc123
```

### Arrêter/Redémarrer le conteneur
```bash
# Arrêter
docker stop maroc-mysql

# Démarrer
docker start maroc-mysql

# Redémarrer
docker restart maroc-mysql
```

### Supprimer complètement (données perdues !)
```bash
docker stop maroc-mysql
docker rm maroc-mysql
```

### Sauvegarder la base de données
```bash
# Créer un dump
docker exec maroc-mysql mysqldump -umaroc_user -pmaroc123 maroc_inflation > backup_$(date +%Y%m%d).sql

# Restaurer depuis un dump
docker exec -i maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation < backup_20251228.sql
```

---

## 🐛 Dépannage

### Erreur : "port 3306 already allocated"
Un autre MySQL utilise déjà le port 3306.

**Solution 1 :** Arrêter le MySQL système
```bash
sudo systemctl stop mariadb
# OU
sudo service mariadb stop
```

**Solution 2 :** Utiliser un port différent (par exemple 3307)
```bash
docker run --name maroc-mysql \
  -e MYSQL_ROOT_PASSWORD=maroc123 \
  -e MYSQL_DATABASE=maroc_inflation \
  -e MYSQL_USER=maroc_user \
  -e MYSQL_PASSWORD=maroc123 \
  -p 3307:3306 \  # <-- Port modifié
  -d mysql:8.0

# Puis modifier .env :
# DB_HOST=127.0.0.1:3307
```

### Erreur : "Can't connect to MySQL server"
Le conteneur n'est pas encore prêt.

```bash
# Attendre 10 secondes de plus
sleep 10

# Vérifier les logs
docker logs maroc-mysql | grep "ready for connections"
```

### Erreur : "Access denied for user"
Vérifier les credentials dans `.env`.

```bash
# Afficher les variables d'environnement du conteneur
docker exec maroc-mysql env | grep MYSQL
```

### Le conteneur s'arrête immédiatement
```bash
# Voir pourquoi il s'est arrêté
docker logs maroc-mysql

# Vérifier l'état
docker ps -a | grep maroc-mysql
```

---

## 💾 Persistance des données (optionnel)

Par défaut, les données sont perdues si vous supprimez le conteneur. Pour les conserver :

```bash
# Créer un volume Docker
docker volume create maroc-mysql-data

# Démarrer avec le volume
docker run --name maroc-mysql \
  -e MYSQL_ROOT_PASSWORD=maroc123 \
  -e MYSQL_DATABASE=maroc_inflation \
  -e MYSQL_USER=maroc_user \
  -e MYSQL_PASSWORD=maroc123 \
  -p 3306:3306 \
  -v maroc-mysql-data:/var/lib/mysql \  # <-- Volume persistant
  -d mysql:8.0 \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_unicode_ci
```

---

## 🎯 Script tout-en-un

Copiez ce script pour automatiser tout le processus :

```bash
#!/bin/bash
set -e

echo "🐳 Démarrage de MySQL Docker..."
docker stop maroc-mysql 2>/dev/null || true
docker rm maroc-mysql 2>/dev/null || true

docker run --name maroc-mysql \
  -e MYSQL_ROOT_PASSWORD=maroc123 \
  -e MYSQL_DATABASE=maroc_inflation \
  -e MYSQL_USER=maroc_user \
  -e MYSQL_PASSWORD=maroc123 \
  -p 3306:3306 \
  -d mysql:8.0 \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_unicode_ci

echo "⏳ Attente du démarrage MySQL (15s)..."
sleep 15

echo "📊 Import de la structure SQL..."
docker exec -i maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation < sql/database.sql

echo "✅ Vérification des tables..."
docker exec maroc-mysql mysql -umaroc_user -pmaroc123 -e "USE maroc_inflation; SHOW TABLES;"

echo ""
echo "🎉 Installation Docker terminée !"
echo ""
echo "Commandes utiles :"
echo "  - Tester : CHECK_DB=1 php tests/smoke.php"
echo "  - Serveur : php -S localhost:8000 -t public"
echo "  - Logs : docker logs -f maroc-mysql"
echo "  - MySQL : docker exec -it maroc-mysql mysql -umaroc_user -pmaroc123 maroc_inflation"
```

Sauvegardez ce script dans `setup-docker-mysql.sh` et exécutez :
```bash
chmod +x setup-docker-mysql.sh
./setup-docker-mysql.sh
```

---

## ✅ Checklist finale

- [ ] Docker installé et fonctionnel
- [ ] Conteneur `maroc-mysql` créé et en cours d'exécution
- [ ] Base `maroc_inflation` créée avec 11 tables
- [ ] Fichier `.env` avec les bons credentials (maroc_user/maroc123)
- [ ] Test `CHECK_DB=1 php tests/smoke.php` réussi ✅
- [ ] Serveur PHP démarré sur http://localhost:8000

---

**🎉 Votre environnement est prêt avec Docker !**
