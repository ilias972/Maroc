# Synchronisation & Imports

## Cron quotidien

- Script : `php data/cron_daily_sync.php`
- Schedule recommandé : `0 2 * * *` (02h00)
- Journalisation : `logs/sync.log` (ajout de chaque run)

## Ordre d’exécution

1) **Bank Al-Maghrib** (jours ouvrés)  
   - `data/import_bank_al_maghrib.php`
2) **HCP (CKAN)** (quotidien)  
   - `data/import_hcp_ckan.php`
3) **World Bank** (lundi uniquement)  
   - `data/import_world_bank.php`

Les jobs sont tracés avec un statut `✅/❌/⏭️` dans la sortie et dans `logs/sync.log` avec horodatage.

## Lancer manuellement

```bash
php data/cron_daily_sync.php
```

## Bonnes pratiques

- Vérifier le fichier `.env` (DB_HOST, DB_NAME, DB_USER, DB_PASS) avant de lancer les imports.
- Surveiller `logs/sync.log` après chaque run (échec visible avec `❌` et code retour).
- Activer le test DB dans `tests/smoke.php` en exportant `CHECK_DB=1` si une base est accessible lors du CI local.
- En cas d’échec répété, exécuter les scripts d’import individuellement pour obtenir le détail de l’erreur.
- Définir `SYNC_WEBHOOK_URL` pour envoyer une alerte (Slack/webhook HTTP) si un job échoue.
