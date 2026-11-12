#!/usr/bin/env bash
# Sauvegarde quotidienne des bases PostgreSQL (pg_dump + gzip).
# Execution par cron dans le conteneur db-backup (tous les jours a 02:00).
set -euo pipefail

BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_USER="${DB_USER:-patrimoine}"
DB_HOST="postgres"
RETENTION_DAYS=30

DBS="auth_db organisation_db asset_db movement_db inventory_db maintenance_db valuation_db document_db notification_db reporting_db"

mkdir -p "$BACKUP_DIR/daily"

for db in $DBS; do
  echo "[$(date '+%F %T')] Dump de $db ..."
  PGPASSWORD="$DB_PASSWORD" pg_dump -h "$DB_HOST" -U "$DB_USER" -Fc "$db" \
    | gzip > "$BACKUP_DIR/daily/${db}_${DATE}.dump.gz"
done

# Purge des sauvegardes de plus de RETENTION_DAYS jours
find "$BACKUP_DIR/daily" -name '*.dump.gz' -mtime +$RETENTION_DAYS -delete

echo "[$(date '+%F %T')] Sauvegarde terminee."
