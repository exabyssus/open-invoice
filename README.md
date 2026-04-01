# Open Invoice

Multi-tenant invoice management system built with Laravel 13 and Filament 5.

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Build frontend assets:

```bash
npm install
npm run build
```

## Create a super-admin

Create a new super-admin user:

```bash
php artisan make:super-admin --create
```

Or promote an existing user:

```bash
php artisan make:super-admin --email=user@example.com
```

Super-admins can:
- Access all tenants and switch between them
- Create, edit and delete companies (tenants) from the **Companies** resource
- Attach and detach users to/from any company

## Run

```bash
php artisan serve
```

Open http://localhost:8000 in your browser.

## Multi-tenancy

Each user can belong to multiple companies. Users with more than one company see a tenant switcher in the sidebar to switch between them.

Super-admins see all companies in the switcher regardless of membership.

## Testing

```bash
php artisan test
```

## Using MySQL instead of SQLite

Update `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=open_invoice
DB_USERNAME=root
DB_PASSWORD=
```
