# Revactyl Credit Hosting (Laravel 10 + Pterodactyl Application API)

Production-oriented Laravel 10 application skeleton for credit-based free server hosting integrated with Pterodactyl Panel through the Application API only.

## Key Guarantees

- Pterodactyl core is untouched (external integration only).
- API credentials are loaded from `.env` (`PTERO_URL`, `PTERO_API_KEY`, `PTERO_NODE_ID`, `PTERO_EGG_ID`).
- Service-based architecture (`app/Services`) with thin controllers.
- Credit, redeem, ad reward, and billing operations use database transactions.
- Duplicate ad webhooks are blocked via unique provider transaction id.
- Duplicate server creation and credit race conditions are mitigated with `lockForUpdate()`.

## Full Project Structure

```text
artisan
composer.json
.env.example
README.md
app/
	Console/
		Kernel.php
		Commands/
			ProcessBillingCommand.php
	Exceptions/
		Handler.php
	Http/
		Kernel.php
		Controllers/
			Controller.php
			AuthController.php
			DashboardController.php
			RedeemController.php
			Admin/
				AdminDashboardController.php
				RedeemCodeAdminController.php
				SettingsController.php
			Api/
				AdWebhookController.php
		Middleware/
			AdminMiddleware.php
			Authenticate.php
			EncryptCookies.php
			EnsureEmailVerified.php
			RedirectIfAuthenticated.php
			TrimStrings.php
			TrustProxies.php
			VerifyCsrfToken.php
	Models/
		AdRewardLog.php
		Credit.php
		CreditTransaction.php
		RedeemCode.php
		RedeemLog.php
		SystemSetting.php
		User.php
		UserServer.php
	Providers/
		AppServiceProvider.php
		AuthServiceProvider.php
		RouteServiceProvider.php
	Repositories/
		CreditRepository.php
		SystemSettingRepository.php
	Services/
		AdRewardService.php
		BillingService.php
		CreditService.php
		PterodactylService.php
		RedeemCodeService.php
		ServerProvisionService.php
bootstrap/
	app.php
	providers.php
config/
	app.php
	auth.php
	database.php
	services.php
database/
	migrations/
		2026_02_21_000001_create_users_table.php
		2026_02_21_000002_create_system_settings_table.php
		2026_02_21_000003_create_credits_table.php
		2026_02_21_000004_create_credit_transactions_table.php
		2026_02_21_000005_create_ad_rewards_log_table.php
		2026_02_21_000006_create_redeem_codes_table.php
		2026_02_21_000007_create_redeem_logs_table.php
		2026_02_21_000008_create_user_servers_table.php
		2026_02_21_000009_create_sessions_table.php
		2026_02_21_000010_create_jobs_tables.php
	seeders/
		DatabaseSeeder.php
		SystemSettingsSeeder.php
public/
	index.php
resources/
	views/
		admin/
			index.blade.php
			redeem-codes.blade.php
			settings.blade.php
		auth/
			login.blade.php
			register.blade.php
			verify-email.blade.php
		dashboard/
			index.blade.php
		layouts/
			app.blade.php
routes/
	api.php
	console.php
	web.php
```

## Required Feature Mapping

- Authentication + email verification: `AuthController`, verify routes in `routes/web.php`, `User` implements `MustVerifyEmail`.
- Registration IP controls: `users.register_ip` + max 2 per IP in `AuthController::register`.
- Rate limits: `AppServiceProvider` defines `login`, `register`, `redeem`, `ad-webhook` limiters.
- System settings: `system_settings` migration, seeder defaults, admin edit in `SettingsController`.
- Credits: `CreditService` + `CreditRepository` transaction-safe award/charge with transaction logs.
- Ad rewards: `AdWebhookController` + `AdRewardService` with signature validation, duplicate prevention, per-day limit.
- Redeem codes: `RedeemCodeService` with expiry/active/max/per-user checks in transaction.
- Free server claim: `ServerProvisionService` creates panel server then records `user_servers` in transaction.
- Billing automation: `billing:process` command + scheduler hourly in `Console\Kernel`.
- Auto-unsuspend: implemented in `BillingService::process` when user balance is restored.
- Admin panel: settings, redeem CRUD, user credit adjust, force suspend/delete, logs + revenue estimate.

## Example Components (Requested)

- Example migrations:
	- `database/migrations/2026_02_21_000003_create_credits_table.php`
	- `database/migrations/2026_02_21_000008_create_user_servers_table.php`
- Example service class:
	- `app/Services/CreditService.php`
	- `app/Services/PterodactylService.php`
- Billing command example:
	- `app/Console/Commands/ProcessBillingCommand.php`
- Ad webhook controller example:
	- `app/Http/Controllers/Api/AdWebhookController.php`
- Redeem controller example:
	- `app/Http/Controllers/RedeemController.php`
- Pterodactyl API integration example:
	- `app/Services/PterodactylService.php`
	- `app/Services/ServerProvisionService.php`
- Admin settings controller example:
	- `app/Http/Controllers/Admin/SettingsController.php`

## Install / Run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Default seeded admin (change in `.env` before seeding):

- `INITIAL_ADMIN_EMAIL=admin@example.com`
- `INITIAL_ADMIN_PASSWORD=ChangeMe123!`

If your container does not have `pdo_mysql` installed, use SQLite locally:

```bash
touch database/database.sqlite
php -r "file_put_contents('.env', preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=sqlite', file_get_contents('.env')));"
php -r "file_put_contents('.env', preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=' . str_replace('/', '\\/','database/database.sqlite'), file_get_contents('.env')));"
php artisan migrate:fresh --seed --force
```

## Test It End-to-End

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
```

If you want isolated CI-style tests using in-memory SQLite:

```bash
php artisan test --testsuite=Feature
```

Then execute billing simulation:

```bash
php artisan billing:process
```

Live Pterodactyl connectivity smoke check (safe, non-destructive):

```bash
php artisan ptero:smoke-test
```

What it verifies:

- Lists all Application API nodes detected on your panel.
- Resolves and prints node IP addresses.
- Validates that configured `PTERO_NODE_ID` (or `--node`) exists in that list.
- Checks selected node resource endpoint accessibility.
- Syncs discovered nodes into `pterodactyl_nodes` table with availability + last seen timestamp.

Optional node override:

```bash
php artisan ptero:smoke-test --node=2
```

The command is blocked in production unless forced:

```bash
php artisan ptero:smoke-test --force
```

## Production Notes

- Set real queue/session/cache drivers (Redis recommended).
- Enforce HTTPS and secure cookies at reverse proxy.
- Add monitoring/alerts around `billing:process` failures.
- Keep `AD_WEBHOOK_SECRET` and Pterodactyl key rotated and secret-managed.
- Rotate initial admin credentials immediately after first login.

## Standalone Ubuntu 22.04 Deployment

Use the fully automated VPS deployment bundle:

- `deploy/standalone/ubuntu-22.04/install.sh`
- `deploy/standalone/ubuntu-22.04/nginx/revactyl.conf`
- `deploy/standalone/ubuntu-22.04/systemd/revactyl-queue.service`
- `deploy/standalone/ubuntu-22.04/systemd/revactyl-scheduler.service`
- `deploy/standalone/ubuntu-22.04/.env.production.example`

Quick start:

```bash
chmod +x deploy/standalone/ubuntu-22.04/install.sh
sudo DOMAIN=your-domain.example \
	ADMIN_EMAIL=ops@example.com \
	REPO_URL=https://github.com/Luffy998899/larvel.git \
	BRANCH=main \
	bash deploy/standalone/ubuntu-22.04/install.sh
```

Detailed guide:

- `deploy/standalone/ubuntu-22.04/README.md`
