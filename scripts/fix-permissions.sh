#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

mkdir -p "$PROJECT_ROOT/storage/uploads/photos" "$PROJECT_ROOT/storage/generated" "$PROJECT_ROOT/storage/logs"

chmod -R 777 "$PROJECT_ROOT/storage"
find "$PROJECT_ROOT/storage" -type d -exec chmod 777 {} \;
find "$PROJECT_ROOT/storage" -type f -exec chmod 666 {} \;

echo "Permisos de escritura aplicados a storage/."
echo "En producción, ajuste el propietario al usuario del servidor web (www-data)."
