# Mani Rēķini (open-invoice)

Mani Rēķini is a multi-company invoicing app. It runs at https://rekini.agw.lv (https://manirekini.agw.lv redirects there, same path).
A user belongs to one or more companies and works inside one company at a time.
In a company you keep clients and products, write invoices, download them as PDF and mail them.
Recurring invoices are made automatically from schedules. Members join a company by invitation.

## Shape

- PHP 8.5, Laravel 13, Filament 5 (admin panel at `/panel`), Pest 3.
- PDF: `barryvdh/laravel-dompdf`. Money: `moneyphp/money`. Errors: `sentry/sentry-laravel`.
- Languages: `lang/lv`, `lang/en`. Timezone: `Europe/Riga`.
- Docker image: `php:8.5-apache` with bcmath, gd, intl, pdo_mysql, opcache (`Dockerfile`, `docker/entrypoint.sh`).

Main folders:

- `app/Models`: `Company` (the tenant), `User`, `CompanyInvitation`, `Client`, `Product`, `Invoice`, `InvoiceLine`, `InvoiceSchedule`.
- `app/Filament`: resources, pages and dashboard widgets of the panel.
- `app/Services`: invoice numbers, PDF, amount in words, running schedules.
- `app/Console/Commands`: artisan commands.
- `resources/views/invoices`: the PDF template. `resources/views/emails`: the invoice mail.

Features:

- **Tenancy.** Filament tenancy by `Company`. Users with more than one company get a switcher. Super-admins see all companies and can manage companies and members.
- **Invoice numbers.** Each company picks a number style: yearly counter, all-time counter, date (`Y.m.d`), short date (`dmy-`, rest typed by hand) or none. The prefix and suffix may contain `CUSTOMER_CODE` (or `{{CUSTOMER_CODE}}`), which is replaced with the client's code.
- **PDF.** `GenerateInvoicePdf` renders the invoice with dompdf (A4, DejaVu Sans). Company logos are stored on the `private` disk.
- **Scheduled invoices.** An `InvoiceSchedule` is monthly or yearly. `php artisan invoices:run-schedules` makes the invoices that are due and mails them when auto-send is on. The Laravel scheduler runs it daily at 06:00 (`routes/console.php`), so `php artisan schedule:run` must run every minute.
- **Mail.** `InvoiceMail` sends the invoice with the PDF attached; reply-to is the company owner. Invitations are sent as notifications.

Make a super-admin:

```bash
php artisan make:super-admin --create                  # new user
php artisan make:super-admin --email=user@example.com  # promote an existing user
```

## Local development

Default setup uses SQLite. No frontend build is needed: Filament ships its own assets.

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan make:super-admin --create
php artisan serve
```

Open http://localhost:8000/panel. Mail goes to the SMTP server in `.env` (Mailpit on port 1025 by default); set `MAIL_MAILER=log` to write mails to the log instead.

For MySQL/MariaDB, set `DB_CONNECTION=mysql` and the `DB_*` values in `.env`.

To make due scheduled invoices by hand:

```bash
php artisan invoices:run-schedules
```

## Tests

Pest tests are in `tests/Feature` and `tests/Unit`. They use in-memory SQLite (`phpunit.xml`).

```bash
php artisan test
```

## Deploy

- Production runs in Coolify on the AGW server, built from the `Dockerfile`.
- A push to `master` deploys automatically (GitHub webhook).
- The Coolify app, database, environment and volume are set up by `coolify/open-invoice.sh` in the private infra repo. Change settings there, not by hand.
- Health check: `GET /up`. After each deploy: `php artisan migrate --force`.
- Cron: a Coolify scheduled task runs `php artisan schedule:run` every minute as `www-data`.
- Database: MariaDB 11, internal only.
- Volume: `storage` (logos, dompdf font cache, logs).
- Mail: SMTP2GO over SMTP. Without SMTP credentials `MAIL_MAILER=log`.
- Errors go to Sentry.
- Environment variables: `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`, `LOG_CHANNEL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `SENTRY_LARAVEL_DSN`.
- Make a super-admin in production: run `php artisan make:super-admin --create` in the app container.
