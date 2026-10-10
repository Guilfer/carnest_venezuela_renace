#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Este script debe ejecutarse con sudo: sudo bash scripts/setup-server.sh"
    exit 1
fi

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DB_NAME="carnets_vr"
DB_USER="carnets_vr"
DB_PASS="$(openssl rand -hex 12)"
ADMIN_USER="admin"
ADMIN_PASS="$(openssl rand -base64 9 | tr -d '/+=' | cut -c1-10)"
HOST_NAME="carnets-venezuela-renace.local"
SITE_NAME="carnets-venezuela-renace.conf"

echo "==> Creando base de datos y usuario MySQL"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

echo "==> Instalando esquema y configurando la aplicación"
sudo -u "${SUDO_USER:-root}" php "${PROJECT_ROOT}/scripts/install.php" \
    --host=127.0.0.1 --port=3306 --database="${DB_NAME}" \
    --username="${DB_USER}" --password="${DB_PASS}" \
    --admin-user="${ADMIN_USER}" --admin-name="Administrador" --admin-pass="${ADMIN_PASS}" \
    --no-create-db --force

echo "==> Instalando vhost de Apache"
cp "${PROJECT_ROOT}/deploy/apache/${SITE_NAME}" "/etc/apache2/sites-available/${SITE_NAME}"
a2ensite "${SITE_NAME}" >/dev/null
if ! grep -q "${HOST_NAME}" /etc/hosts; then
    echo "127.0.0.1 ${HOST_NAME}" >> /etc/hosts
fi
apache2ctl configtest
systemctl reload apache2

echo "==> Ajustando permisos de storage"
chown -R www-data:www-data "${PROJECT_ROOT}/storage"
find "${PROJECT_ROOT}/storage" -type d -exec chmod 775 {} \;
find "${PROJECT_ROOT}/storage" -type f -exec chmod 664 {} \;

echo
echo "=============================================="
echo " Instalación completada"
echo "----------------------------------------------"
echo " URL:              http://${HOST_NAME}"
echo " Admin usuario:    ${ADMIN_USER}"
echo " Admin contraseña: ${ADMIN_PASS}"
echo "----------------------------------------------"
echo " MySQL base:       ${DB_NAME}"
echo " MySQL usuario:    ${DB_USER}"
echo " MySQL contraseña: ${DB_PASS}"
echo "=============================================="
echo "Guarde estas credenciales. Verifíquelas luego en config/config.php"
