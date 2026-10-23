---
name: laravel-modules-development
description: Best practices for developing modular Laravel applications using nwidart/laravel-modules
---

# Laravel Modules Development

This project uses `nwidart/laravel-modules`.

## Rules

- Every business domain must live inside its own module.
- Avoid placing domain logic inside the main `app/` directory.
- Each module should contain:
    - Controllers
    - Services
    - Repositories
    - DTOs
    - Requests
    - Models
    - Routes
    - Migrations
    - Tests

## Module Structure

Example:

Modules/
└── Billing/
├── app/
│   ├── Http/
│   ├── Models/
│   ├── Services/
│   ├── Repositories/
│   └── DTOs/
├── routes/
├── database/
└── tests/

## Conventions

- Use service classes for business logic.
- Keep controllers thin.
- Use repository pattern when querying complex data.
- Use DTOs between layers.
- Each module must be independent when possible.
- Register routes inside module service providers.
- Prefer module-scoped configs and translations.

## Commands

Create module:

```bash
php artisan module:make Billing
