#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SITE_NAME="carnets-venezuela-renace.conf"
HOST="carnets-venezuela-renace.local"
SOURCE="$PROJECT_ROOT/deploy/apache/$SITE_NAME"
DEST="/etc/apache2/sites-available/$SITE_NAME"

echo "Instalando vhost para $HOST ..."
sudo cp "$SOURCE" "$DEST"
sudo a2ensite "$SITE_NAME"

if ! grep -q "$HOST" /etc/hosts; then
    echo "127.0.0.1 $HOST" | sudo tee -a /etc/hosts >/dev/null
    echo "Agregado $HOST a /etc/hosts"
fi

sudo apache2ctl configtest
sudo systemctl reload apache2

echo "Listo. Abra http://$HOST"
