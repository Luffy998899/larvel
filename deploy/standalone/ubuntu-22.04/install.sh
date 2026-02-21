#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash deploy/standalone/ubuntu-22.04/install.sh"
  exit 1
fi

DOMAIN="${DOMAIN:-}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
APP_DIR="${APP_DIR:-/var/www/revactyl-host}"
REPO_URL="${REPO_URL:-https://github.com/Luffy998899/larvel.git}"
BRANCH="${BRANCH:-main}"
APP_USER="${APP_USER:-www-data}"
USE_SQLITE="${USE_SQLITE:-false}"
DB_NAME="${DB_NAME:-revactyl_host}"
DB_USER="${DB_USER:-revactyl_user}"
DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"
APP_ENV="${APP_ENV:-production}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-https://${DOMAIN}}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"
SESSION_DRIVER="${SESSION_DRIVER:-database}"
CACHE_DRIVER="${CACHE_DRIVER:-file}"

if [[ -z "${DOMAIN}" ]]; then
  echo "Set DOMAIN before running. Example:"
  echo "DOMAIN=host.example.com ADMIN_EMAIL=ops@example.com bash deploy/standalone/ubuntu-22.04/install.sh"
  exit 1
fi

echo "==> Installing OS packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y nginx git unzip curl ca-certificates lsb-release software-properties-common \
  php8.1-fpm php8.1-cli php8.1-common php8.1-mysql php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-bcmath php8.1-intl php8.1-gd \
  mysql-server composer redis-server certbot python3-certbot-nginx

systemctl enable --now nginx
systemctl enable --now php8.1-fpm
systemctl enable --now redis-server
systemctl enable --now mysql

mkdir -p "${APP_DIR}"
if [[ ! -d "${APP_DIR}/.git" ]]; then
  echo "==> Cloning repository"
  git clone --branch "${BRANCH}" "${REPO_URL}" "${APP_DIR}"
else
  echo "==> Updating repository"
  git -C "${APP_DIR}" fetch --all --prune
  git -C "${APP_DIR}" checkout "${BRANCH}"
  git -C "${APP_DIR}" pull --ff-only origin "${BRANCH}"
fi

cd "${APP_DIR}"

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

upsert_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s#^${key}=.*#${key}=${value}#" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

echo "==> Configuring .env"
upsert_env APP_NAME "RevactylHost"
upsert_env APP_ENV "${APP_ENV}"
upsert_env APP_DEBUG "${APP_DEBUG}"
upsert_env APP_URL "${APP_URL}"
upsert_env LOG_CHANNEL "stack"
upsert_env LOG_LEVEL "info"
upsert_env CACHE_DRIVER "${CACHE_DRIVER}"
upsert_env SESSION_DRIVER "${SESSION_DRIVER}"
upsert_env QUEUE_CONNECTION "${QUEUE_CONNECTION}"

if [[ "${USE_SQLITE}" == "true" ]]; then
  mkdir -p database
  touch database/database.sqlite
  upsert_env DB_CONNECTION "sqlite"
  upsert_env DB_DATABASE "database/database.sqlite"
else
  upsert_env DB_CONNECTION "mysql"
  upsert_env DB_HOST "127.0.0.1"
  upsert_env DB_PORT "3306"
  upsert_env DB_DATABASE "${DB_NAME}"
  upsert_env DB_USERNAME "${DB_USER}"
  upsert_env DB_PASSWORD "${DB_PASS}"

  echo "==> Preparing MySQL database"
  mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
fi

echo "==> Installing PHP dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo "==> Running migrations and seeders"
php artisan migrate --force --seed

echo "==> Optimizing Laravel"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Setting permissions"
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
find "${APP_DIR}/storage" -type d -exec chmod 775 {} \;
find "${APP_DIR}/bootstrap/cache" -type d -exec chmod 775 {} \;

echo "==> Configuring nginx"
cp deploy/standalone/ubuntu-22.04/nginx/revactyl.conf /etc/nginx/sites-available/revactyl
sed -i "s#{{DOMAIN}}#${DOMAIN}#g" /etc/nginx/sites-available/revactyl
sed -i "s#{{APP_DIR}}#${APP_DIR}#g" /etc/nginx/sites-available/revactyl
ln -sf /etc/nginx/sites-available/revactyl /etc/nginx/sites-enabled/revactyl
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "==> Configuring systemd workers"
cp deploy/standalone/ubuntu-22.04/systemd/revactyl-queue.service /etc/systemd/system/revactyl-queue.service
cp deploy/standalone/ubuntu-22.04/systemd/revactyl-scheduler.service /etc/systemd/system/revactyl-scheduler.service
sed -i "s#{{APP_DIR}}#${APP_DIR}#g" /etc/systemd/system/revactyl-queue.service
sed -i "s#{{APP_DIR}}#${APP_DIR}#g" /etc/systemd/system/revactyl-scheduler.service
systemctl daemon-reload
systemctl enable --now revactyl-queue.service
systemctl enable --now revactyl-scheduler.service

echo "==> Enabling HTTPS certificate"
certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos --email "${ADMIN_EMAIL}" --redirect || true

echo "==> Deployment completed"
echo "App URL: ${APP_URL}"
echo "If using MySQL, DB user password: ${DB_PASS}"
echo "Run smoke checks:"
echo "  php artisan test"
echo "  php artisan ptero:smoke-test --force"
