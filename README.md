# POS

Point-of-sale application built as a modular Laravel monolith.

## Tech Stack

- **Backend:** PHP 8.4, Laravel 13, Filament 5, Fortify, Sanctum, Telescope
- **Frontend:** Vue 3 + TypeScript, Inertia 3, Tailwind CSS 4, Vite, Wayfinder (typed routes)
- **Modules:** nwidart/laravel-modules
- **Packages:** spatie/laravel-medialibrary, spatie/laravel-permission
- **Testing:** PHPUnit

## Requirements

- PHP 8.4+
- Composer
- Node.js 22+
- A supported database (see `.env`)

## Getting Started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed
```

## Development

```bash
composer run dev     # serves app + queue + vite dev server
# or individually:
php artisan serve
npm run dev
```

### Scripts

| Command             | Description                        |
| ------------------- | ---------------------------------- |
| `npm run dev`       | Vite dev server                    |
| `npm run build`     | Production assets                  |
| `npm run build:ssr` | Production assets + SSR bundle     |
| `npm run lint`      | ESLint (fix)                       |
| `npm run format`    | Prettier                           |
| `npm run types:check` | `vue-tsc` type check             |

### Testing & Quality

```bash
php artisan test --compact
vendor/bin/pint --dirty
```

## Structure

Application code is organized into modules under `Modules/`, each self-contained with its own routes, controllers, models, and views:

- `Tenancy` – tenant registration & current-tenant resolution
- `Identity` – users, roles, permissions
- `Billing` – subscriptions & payments
- `Customers`
- `Inventory`
- `Restaurant`
- `Sales`
- `Settings`
- `Reports`
- `Offline`
- `Shared` – cross-module utilities

Shared frontend code lives in `resources/js`; generated Wayfinder route/controller bindings are in `resources/js/actions` and `resources/js/routes`.
