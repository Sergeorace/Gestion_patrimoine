#!/usr/bin/env bash
# Restauration d'une base PostgreSQL depuis un dump.
# Usage : ./restore.sh <nom_base> <fichier.dump.gz>
set -euo pipefail

DB_NAME="${1:?Usage: restore.sh <base> <fichier.dump.gz>}"
DUMP="${2:?Usage: restore.sh <base> <fichier.dump.gz>}"
DB_USER="${DB_USER:-patrimoine}"
DB_HOST="${DB_HOST:-postgres}"

[ -f "$DUMP" ] || { echo "Fichier introuvable : $DUMP"; exit 1; }

echo "Restauration de $DB_NAME depuis $DUMP ..."
echo "ATTENTION : le schema existant sera remplace."
read -r -p "Confirmer (oui/non) : " confirm
[ "$confirm" = "oui" ] || exit 1

PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d postgres \
  -c "DROP DATABASE IF EXISTS ${DB_NAME};"
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d postgres \
  -c "CREATE DATABASE ${DB_NAME};"

zcat "$DUMP" | PGPASSWORD="$DB_PASSWORD" pg_restore -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" --no-owner --no-privileges

echo "Restauration de $DB_NAME terminee."
