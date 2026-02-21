# Standalone Ubuntu 22.04 Deployment

This setup deploys the app on a single VPS with:

- Nginx
- PHP 8.1 FPM
- MySQL (default) or SQLite
- Redis
- systemd queue worker and scheduler
- TLS via Certbot

## 1) Run installer

```bash
cd /path/to/larvel
chmod +x deploy/standalone/ubuntu-22.04/install.sh
sudo DOMAIN=your-domain.example \
  ADMIN_EMAIL=ops@example.com \
  REPO_URL=https://github.com/Luffy998899/larvel.git \
  BRANCH=main \
  bash deploy/standalone/ubuntu-22.04/install.sh
```

Use SQLite mode:

```bash
sudo DOMAIN=your-domain.example USE_SQLITE=true bash deploy/standalone/ubuntu-22.04/install.sh
```

## 2) Configure Pterodactyl credentials

Update app environment file:

```bash
sudo nano /var/www/revactyl-host/.env
```

Set:

- `PTERO_URL`
- `PTERO_API_KEY`
- `PTERO_NODE_ID`
- `PTERO_EGG_ID`

Then apply cache refresh:

```bash
cd /var/www/revactyl-host
php artisan config:clear
php artisan config:cache
```

## 3) Verify deployment

```bash
cd /var/www/revactyl-host
php artisan test
php artisan ptero:smoke-test --force
systemctl status revactyl-queue.service --no-pager
systemctl status revactyl-scheduler.service --no-pager
```

## 4) Logs

- `/var/log/revactyl-queue.log`
- `/var/log/revactyl-queue-error.log`
- `/var/log/revactyl-scheduler.log`
- `/var/log/revactyl-scheduler-error.log`
