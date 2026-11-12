#!/usr/bin/env bash
# Genere les fichiers .env de chaque service a partir du .env racine.
# Usage : cp .env.example .env  puis  ./tools/init_env.sh
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { echo "ERREUR : .env racine absent. Copiez .env.example d'abord."; exit 1; }

# Charge les variables du .env racine
set -a; . ./.env; set +a

substitute() {
  # Remplace ${VAR} et ${VAR:-defaut} par les valeurs du .env racine
  local file="$1"
  while IFS= read -r line; do
    eval "echo \"$line\"" 2>/dev/null || echo "$line"
  done < "$file" > "$file.tmp" && mv "$file.tmp" "$file"
}

for envfile in services/*/.env.example frontend/.env.example; do
  [ -f "$envfile" ] || continue
  target="${envfile%.example}"
  cp "$envfile" "$target"
  substitute "$target"
  echo "Genere : $target"
done

echo "Termine. Verifiez que les secrets sont correctement injectes."
