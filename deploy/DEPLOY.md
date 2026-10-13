# Despliegue de Carnets Venezuela Renace en otro servidor

Guía paso a paso para instalar el sistema en un servidor nuevo (Debian 12 / Ubuntu 22.04-24.04).
El stack es **PHP puro (sin framework) + Apache + MySQL/MariaDB + ImageMagick + Composer**.

---

## 0. Resumen del stack y requisitos

| Componente | Requisito |
|---|---|
| SO | Debian 12 (bookworm) o Ubuntu 22.04/24.04 |
| PHP | **8.4 (recomendada)** – mínimo 8.1, ver nota en la tabla |
| Extensiones PHP | `pdo`, `pdo_mysql`, `mbstring`, `gd`, `imagick`, `zip`, `xml`/`simplexml` |
| Base de datos | MySQL 8 o MariaDB 10.6+ |
| Servidor web | Apache 2.4 con `mod_rewrite` y `libapache2-mod-php` |
| Utilidades | `imagemagick`, `unzip`, `git`, `composer` |
| PHP vars | `upload_max_filesize=20M`, `post_max_size=22M`, `memory_limit>=512M` |

Notas importantes:
- **Versión recomendada: PHP 8.4.** El `composer.lock` fija `endroid/qr-code 6.1.3`, que exige `php ^8.4`. En 8.3 no instala con el lock actual; en 8.5 funcionaría pero 8.4 es la probada en el proyecto (el código de la app usa solo features de 8.1).
- El `DocumentRoot` **debe** apuntar a la carpeta `public/` (no a la raíz del proyecto).
- `public/.htaccess` usa directivas `php_value`, que **solo funcionan con `mod_php`** (no con php-fpm). Si usas php-fpm, mueve esos límites a `php.ini` y elimina las 3 primeras líneas del `.htaccess`.
- El QR impreso codifica `carnet.verify_base` + token. **Si cambia el dominio hay que regenerar todos los carnets** (ver paso 9).
- `spa.traineddata` y la carpeta `info/` no se usan en tiempo de ejecución (solo `info/` para la migración masiva del Excel). No son dependencias del sistema.

---

## 1. Instalar paquetes del sistema

```bash
sudo apt update && sudo apt upgrade -y

# Apache + MariaDB
sudo apt install -y apache2 mariadb-server

# ---- PHP 8.4 (recomendada) ----
# Ubuntu 24.04/22.04 usa el PPA ondrej/php:
#   sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
# Debian 12 no trae 8.4 en sus repos; se usa Sury:
sudo apt install -y apt-transport-https lsb-release ca-certificates curl
sudo curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg \
  https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] \
  https://packages.sury.org/php/ $(lsb_release -sc) main" \
  | sudo tee /etc/apt/sources.list.d/php-sury.list
sudo apt update

# PHP 8.4 + extensiones requeridas por composer.json y el código
sudo apt install -y \
  php8.4 php8.4-cli libapache2-mod-php8.4 \
  php8.4-mysql php8.4-mbstring php8.4-gd php8.4-imagick \
  php8.4-zip php8.4-xml php8.4-curl php8.4-opcache

# Herramientas
sudo apt install -y imagemagick unzip git curl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

> ¿Necesitas otra versión? El lock actual **no instala en PHP 8.3** (endroid pide `^8.4`). Para 8.3, baja la dependencia primero: `composer require endroid/qr-code:^6.0 --update-with-dependencies`.

Verifica extensiones:
```bash
php -m | grep -iE 'pdo_mysql|mbstring|gd|imagick|zip|xml'
```

---

## 2. Configurar PHP (`php.ini`)

Edita el `php.ini` de Apache (y el de CLI para los scripts):

```bash
sudo nano /etc/php/8.4/apache2/php.ini
sudo nano /etc/php/8.4/cli/php.ini
```

Ajusta:
```ini
upload_max_filesize = 20M
post_max_size = 22M
max_file_uploads = 20
max_execution_time = 120
memory_limit = 512M          ; Imagick renderiza imágenes grandes; -1 también sirve
date.timezone = America/Caracas

opcache.enable = 1
opcache.memory_consumption = 128
opcache.validate_timestamps = 1   ; pon 0 en producción y reinicia al desplegar
```

Recarga Apache:
```bash
sudo systemctl restart apache2
php -i | grep -E 'upload_max_filesize|post_max_size|memory_limit'
```

---

## 3. Configurar Apache

```bash
# Módulos necesarios
sudo a2enmod rewrite headers ssl

# Quitar sitio por defecto y crear el del sistema
sudo a2dissite 000-default
sudo cp /var/www/html/carnets_venezuela_renace/deploy/apache/carnets-venezuela-renace.conf \
        /etc/apache2/sites-available/carnets-venezuela-renace.conf
```

Edita el vhost y pon tu dominio real. Debe quedar así (el DocumentRoot apunta a `public`):

```apache
<VirtualHost *:80>
    ServerName carnets.tudominio.gob.ve
    ServerAlias www.carnets.tudominio.gob.ve
    DocumentRoot /var/www/html/carnets_venezuela_renace/public

    <Directory /var/www/html/carnets_venezuela_renace/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/carnets-vr_error.log
    CustomLog ${APACHE_LOG_DIR}/carnets-vr_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite carnets-venezuela-renace
sudo apache2ctl configtest
sudo systemctl reload apache2
```

> Si pruebas localmente sin DNS, agrega `127.0.0.1 carnets.tudominio.gob.ve` a `/etc/hosts`.

---

## 4. Crear base de datos y usuario MySQL

MariaDB en Debian trae `root` con autenticación `unix_socket`, por eso se usa `sudo mysql`.

```bash
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS carnets_vr
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'carnets_vr'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
CREATE USER IF NOT EXISTS 'carnets_vr'@'127.0.0.1' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';

GRANT ALL PRIVILEGES ON carnets_vr.* TO 'carnets_vr'@'localhost';
GRANT ALL PRIVILEGES ON carnets_vr.* TO 'carnets_vr'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
```

---

## 5. Copiar el código al servidor

Opción A – desde git:

```bash
sudo mkdir -p /var/www/html
cd /var/www/html
sudo git clone git@github.com:Guilfer/carnest_venezuela_renace.git carnets_venezuela_renace
```

Opción B – desde el servidor actual (sin git):
```bash
# En el servidor ORIGEN
tar --exclude=vendor --exclude=storage --exclude=.git \
    -czf carnets-src.tar.gz -C /var/www/html carnets_venezuela_renace
scp carnets-src.tar.gz usuario@NUEVO_SERVIDOR:/tmp/

# En el servidor NUEVO
sudo tar -xzf /tmp/carnets-src.tar.gz -C /var/www/html
```

Instala dependencias PHP:
```bash
cd /var/www/html/carnets_venezuela_renace
sudo composer install --no-dev --optimize-autoloader
```

---

## 6. Crear `config/config.php`

El archivo está en `.gitignore`, hay que crearlo en el servidor nuevo.

```bash
cd /var/www/html/carnets_venezuela_renace
sudo cp config/config.sample.php config/config.php
sudo nano config/config.php
```

Contenido:
```php
<?php
return [
    'app' => [
        'name' => 'Carnets Venezuela Renace',
        'timezone' => 'America/Caracas',
        'debug' => false,          // true solo para depurar
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'carnets_vr',
        'username' => 'carnets_vr',
        'password' => 'CAMBIA_ESTA_CLAVE',   // la del paso 4
        'charset' => 'utf8mb4',
    ],
    'carnet' => [
        'verify_base' => 'https://carnets.tudominio.gob.ve/verificar',  // dominio real
        'qr_size' => 700,
        'qr_margin' => 4,
    ],
];
```

---

## 7. Crear esquema y administrador

Dos alternativas:

**A) Instalador interactivo / por parámetros** (crea tablas y el admin):
```bash
cd /var/www/html/carnets_venezuela_renace
php scripts/install.php \
  --host=127.0.0.1 --port=3306 --database=carnets_vr \
  --username=carnets_vr --password='CAMBIA_ESTA_CLAVE' \
  --admin-user=admin --admin-name="Administrador" --admin-pass='ClaveAdminSegura' \
  --no-create-db --force
```

**B) Solo importar el esquema** (sin crear admin, útil si vas a restaurar un dump):
```bash
mysql -u carnets_vr -p carnets_vr < database/schema.sql
```

---

## 8. Permisos de `storage/`

```bash
cd /var/www/html/carnets_venezuela_renace
sudo mkdir -p storage/uploads/photos storage/generated storage/logs
sudo chown -R www-data:www-data storage
sudo find storage -type d -exec chmod 775 {} \;
sudo find storage -type f -exec chmod 664 {} \;

# El código lo maneja el usuario de despliegue, no root
sudo chown -R $USER:www-data /var/www/html/carnets_venezuela_renace
```

También existe `sudo bash scripts/fix-permissions.sh` (usa 777, solo para desarrollo).

---

## 9. Migrar los datos

### 9.1 Base de datos

En el servidor **origen**:
```bash
mysqldump -u carnets_vr -p \
  --single-transaction --routines --triggers --default-character-set=utf8mb4 \
  carnets_vr > carnets_vr_dump.sql
```

Copia y restaura en el servidor **nuevo**:
```bash
scp carnets_vr_dump.sql usuario@NUEVO:/tmp/
mysql -u carnets_vr -p carnets_vr < /tmp/carnets_vr_dump.sql
```

> Si usas `install.php` en el paso 7, este ya inserta el admin; restaurar el dump después sobrescribe la tabla `users` con los usuarios reales.

### 9.2 Archivos de `storage/` (fotos, carnets generados, diseño)

En el **origen**:
```bash
cd /var/www/html/carnets_venezuela_renace
tar czf storage-data.tar.gz storage/uploads storage/generated storage/layout.json
scp storage-data.tar.gz usuario@NUEVO:/tmp/
```

En el **nuevo**:
```bash
cd /var/www/html/carnets_venezuela_renace
sudo tar xzf /tmp/storage-data.tar.gz
sudo chown -R www-data:www-data storage
```

- `storage/uploads/photos`: **obligatorio** (fotos subidas).
- `storage/layout.json`: **obligatorio** si se personalizó el diseño (si no existe, se usan los defaults de `config/layout.php`).
- `storage/generated`: opcional; si falta, los carnets se regeneran al abrirse. Igual conviene copiarlo.

### 9.3 Migración masiva desde Excel (opcional, si NO restauras dump)

El repo incluye `info/LISTADO DE PERSONAL PARA ELABORACION DE CARNET.xlsx`.
```bash
cd /var/www/html/carnets_venezuela_renace
php scripts/migrate-listado.php --dry-run     # revisa la salida primero
php scripts/migrate-listado.php               # inserta realmente
```

### 9.4 Regenerar QR (si cambió el dominio)

Como el QR apunta a `verify_base`, tras cambiar de dominio hay que regenerar cada carnet
(botón "Regenerar" en la ficha, o endpoint `POST carnets/{id}/regenerar`). Si ya copiaste
`storage/generated` del servidor viejo, esos QR apuntarán al dominio antiguo.

---

## 10. Activar HTTPS (recomendado en producción)

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d carnets.tudominio.gob.ve
```

Luego actualiza `carnet.verify_base` en `config/config.php` a `https://...` y **regenera los carnets** (paso 9.4).

---

## 11. Verificación

```bash
curl -I http://carnets.tudominio.gob.ve/          # debe responder 302 a /login o 200
sudo tail -f /var/log/apache2/carnets-vr_error.log
sudo tail -f /var/www/html/carnets_venezuela_renace/storage/logs/*.log

# Chequeos rápidos
php -m | grep -i imagick        # extensión Imagick cargada
sudo systemctl status apache2 mariadb --no-pager
mysql -u carnets_vr -p -e "USE carnets_vr; SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM carnets;"
```

Entra a `https://carnets.tudominio.gob.ve/login` con el admin creado (o el del dump).

---

## 12. Alternativa automatizada (solo Debian ya con paquetes instalados)

El repo incluye `scripts/setup-server.sh`, que hace base de datos + instalador + vhost + permisos.
Debes ejecutar antes los pasos 1, 2, 3 y 5 de esta guía.

```bash
cd /var/www/html/carnets_venezuela_renace
sudo bash scripts/setup-server.sh
```

Al final imprime las credenciales generadas (admin y MySQL). Guárdalas.

---

## 13. Respaldos y mantenimiento

```bash
# Dump diario de la base de datos
mysqldump -u carnets_vr -p --single-transaction carnets_vr | gzip > /backups/carnets_$(date +%F).sql.gz

# Respaldo de storage (fotos y diseño)
tar czf /backups/storage_$(date +%F).tar.gz -C /var/www/html/carnets_venezuela_renace storage
```

Cron sugerido (ejemplo, `/etc/cron.d/carnets-backup`):
```cron
0 2 * * * root mysqldump -u carnets_vr -p'CLAVE' --single-transaction carnets_vr | gzip > /backups/carnets_$(date +\%F).sql.gz
```

---

## 14. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| Error 500 al cargar `/` | `config/config.php` ausente o credenciales DB malas | Revisar paso 6 y logs de Apache |
| 500 por `php_value` en `.htaccess` | Se está usando php-fpm en vez de `mod_php` | Instalar `libapache2-mod-php8.4` o mover límites a `php.ini` |
| Rutas 404 (excepto `/`) | `mod_rewrite` desactivado o `AllowOverride None` | `a2enmod rewrite` + `AllowOverride All` |
| No se pueden subir fotos | Límites de PHP bajos | Ajustar `php.ini` (paso 2) |
| No se generan imágenes/QR | Falta `php8.4-imagick` o `php8.4-gd` | Instalar y reiniciar Apache |
| "No se pudo conectar a la base de datos" | Usuario/clave/DB incorrectos | Verificar paso 4 y 6 |
| QR apunta al dominio viejo | `verify_base` desactualizado o carnets no regenerados | Actualizar config y regenerar |
| Permiso denegado al escribir en `storage/` | Propietario incorrecto | `chown -R www-data:www-data storage` |

---

## 15. Orden recomendado (checklist)

1. Instalar paquetes (paso 1)
2. Configurar `php.ini` (paso 2)
3. Configurar Apache y vhost (paso 3)
4. Crear BD y usuario (paso 4)
5. Copiar código + `composer install` (paso 5)
6. Crear `config/config.php` (paso 6)
7. Esquema + admin o restaurar dump (paso 7 / 9.1)
8. Migrar `storage/` (paso 9.2)
9. Permisos (paso 8)
10. HTTPS y regenerar QR (pasos 10 y 9.4)
11. Verificar y respaldar (pasos 11 y 13)
