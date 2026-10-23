---
status: accepted
---

# Admin and vendor panels are Filament; only the storefront is Inertia + Vue

The PRD (D2) chose Inertia + Vue for every surface, but the phase plan replaced that for the two panels, because they are CRUD-heavy and need functionality rather than a custom look. `/admin` and `/vendor` are Filament panels, each module registers its resources through a Filament Plugin, and Inertia + Vue is kept only for the themeable storefront at `/{store-slug}`. The phase plan wins over the PRD where they conflict.

## Considered Options

- Inertia + Vue for everything: full control, but several times the work for tables and forms.
- Filament for the admin only: duplicates the build effort, because the vendor panel needs the same tables and forms.

## Consequences

- shadcn-vue and the component library in the PRD's design system apply to the storefront only.
- Business logic stays in Actions and Services so Filament pages call the same code as any future API.
