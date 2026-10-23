# Implementation Plan: [FEATURE]

**Branch**: `[###-feature-name]` | **Date**: [DATE] | **Spec**: [link]
**Input**: Feature specification from `/specs/[###-feature-name]/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command.

## Summary

[Extract from feature spec: primary requirement + technical approach]

## Technical Context

**Language/Version**: PHP 8.x, TypeScript, Vue 3
**Primary Dependencies**: Laravel, Inertia, Filament or Livewire where applicable, Spatie Permission, Wayfinder
**Storage**: Single relational database with tenant-scoped tables; Redis/cache only when planned
**Testing**: PHPUnit feature/unit tests, focused `php artisan test --compact` runs
**Target Platform**: Web POS/admin application for desktop and tablet browsers
**Project Type**: Laravel + Inertia SaaS web application
**Performance Goals**: [e.g., POS search <200ms with expected data volume, checkout transaction bounded, report cache duration]
**Constraints**: Tenant isolation, permission enforcement, action-centric business logic, RTL/tablet support
**Scale/Scope**: [tenants, users, products, sales volume, offline queue size, report period]

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Tenant Isolation**: List all tenant-owned models/tables and confirm `BelongsToTenant`, middleware, factories, and cross-tenant tests.
- **Action Architecture**: List Action classes and Repository classes; confirm controllers/pages contain no business logic.
- **Testing**: List required unit, feature, integration, and performance tests with the exact command planned for focused verification.
- **Permissions & Audit**: List gates/permissions, high-risk operations, events/notifications/audit records, and role-sensitive UI behavior.
- **Operational UX**: Confirm responsive desktop/tablet behavior, RTL/Arabic requirements, loading/error states, print/offline/payment recovery paths.
- **Dependency Control**: Confirm no new dependency/base directory is required, or document explicit approval and reason.

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
|-- plan.md
|-- research.md
|-- data-model.md
|-- quickstart.md
|-- contracts/
`-- tasks.md
```

### Source Code (repository root)

```text
app/
|-- Actions/
|-- Http/Controllers/
|-- Http/Middleware/
|-- Models/
|-- Policies/
|-- Repositories/
|-- Events/
|-- Jobs/
|-- Notifications/
|-- Services/
database/
|-- factories/
|-- migrations/
|-- seeders/
resources/
|-- js/
|   |-- pages/
|   |-- components/
|   |-- composables/
|   `-- stores/
|-- views/
routes/
tests/
|-- Feature/
`-- Unit/
```

**Structure Decision**: [Document the exact files/directories this feature will use and why.]

## Complexity Tracking

> Fill only if Constitution Check has violations that must be justified.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| [e.g., new dependency] | [current need] | [why existing stack cannot satisfy it] |
