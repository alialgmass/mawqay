<!--
Sync Impact Report
Version change: template -> 1.0.0
Modified principles:
- Placeholder Principle 1 -> I. Tenant Isolation Is Mandatory
- Placeholder Principle 2 -> II. Action-Centric Laravel Architecture
- Placeholder Principle 3 -> III. Test-Enforced Delivery
- Placeholder Principle 4 -> IV. Permissioned, Auditable Operations
- Placeholder Principle 5 -> V. Operational POS Experience
Added sections:
- Technical Constraints
- Delivery Workflow
Removed sections:
- None
Templates requiring updates:
- ✅ updated: .specify/templates/plan-template.md
- ✅ updated: .specify/templates/spec-template.md
- ✅ updated: .specify/templates/tasks-template.md
- ✅ updated: .specify/templates/commands/*.md (directory absent; no action required)
Follow-up TODOs:
- ⚠ pending: .specify/extensions/git/scripts/powershell/initialize-repo.ps1 has a malformed
  string literal and failed before the constitution update.
-->
# POS SaaS System Constitution

## Core Principles

### I. Tenant Isolation Is Mandatory

Every domain model that stores tenant-owned data MUST be tenant scoped by design. Models
MUST use the project `BelongsToTenant` global scope or an explicitly documented exception
approved in the implementation plan. Authenticated web requests MUST resolve the current
tenant from the authenticated user, bind it in the service container, and reject inactive
users before protected business workflows run. Cross-tenant access MUST be covered by
integration tests for every new tenant-owned aggregate.

Rationale: A single-database SaaS POS system fails critically if product, sale, customer,
order, subscription, or report data leaks between stores.

### II. Action-Centric Laravel Architecture

Business behavior MUST live in focused Action classes with explicit typed inputs and return
values. Controllers, Livewire/Filament pages, and Inertia endpoints MUST coordinate request
validation, authorization, and responses only. Database access for feature workflows MUST go
through Repository classes, Eloquent, or the query builder; raw SQL is prohibited unless the
implementation plan documents why Laravel primitives cannot meet a measured requirement.
Status and type fields MUST use PHP backed enums stored as compact database values.

Rationale: POS workflows such as sale processing, stock movement, debt recording, returns,
and subscription renewal need reusable, transaction-safe application services.

### III. Test-Enforced Delivery

Every behavior change MUST include automated tests before it is considered complete. Action
classes require unit tests for happy paths, validation failures, permission failures, and
transactional edge cases. User-facing routes require PHPUnit feature tests that exercise
authorization, validation, and tenant boundaries. Affected tests MUST be run with the
smallest useful `php artisan test --compact` target, and PHP formatting MUST be run with
Pint whenever PHP files change.

Rationale: The POS handles money, stock, debt, and subscriptions; manual verification alone
is not acceptable.

### IV. Permissioned, Auditable Operations

All protected operations MUST enforce permissions server-side with gates, policies, or
Spatie permissions, and SHOULD hide unavailable controls client-side. High-risk operations
including discounts, refunds, user management, profit reporting, stock adjustments, debt
payments, and subscription changes MUST be auditable through persisted records, events, or
notifications. Authentication MUST use Laravel Fortify or Laravel authentication primitives
unless a plan explicitly approves an alternative.

Rationale: Egyptian retail and restaurant operators need reliable accountability for cash,
inventory, employees, and billing.

### V. Operational POS Experience

Interfaces MUST be optimized for repeated cashier and manager workflows, not marketing
presentation. POS surfaces MUST be responsive for desktop and tablet, touch-friendly,
high-density, and usable in RTL/Arabic contexts. Asynchronous operations MUST show loading
states and user-facing errors. Printing, offline queueing, barcode search, and payment flows
MUST prefer predictable recovery over clever UI behavior.

Rationale: The system will be used during active selling periods where latency, unclear
states, or layout failures directly interrupt revenue.

## Technical Constraints

The application is a Laravel, Vue, and Inertia SaaS product for the Egyptian market. New
implementation plans MUST align with the installed project stack: Laravel, Inertia, Vue,
Filament where applicable, Spatie Permission, Spatie Media Library, Fortify, Sanctum,
Wayfinder, PHPUnit, and Pint.

Feature plans MUST document affected tenant-owned entities, permissions, routes, queues,
events, notifications, cache keys, and external integrations. New dependencies, base
directories, or architectural frameworks MUST NOT be introduced without explicit approval in
the plan. Public file access MUST be explicit; media uploads are private by default unless
the feature requires public visibility and tests prove the generated URLs are usable.

Offline and payment features MUST define idempotency keys, retry behavior, and conflict
handling before implementation. Reporting features MUST state how returned items, discounts,
tax, deferred payments, and tenant scoping affect every aggregate.

## Delivery Workflow

Specifications MUST be organized as independently testable user stories ordered by
business priority. Plans MUST pass the Constitution Check before design work proceeds and
again before task generation. Tasks MUST include explicit file paths, tests, authorization,
tenant isolation, and data integrity work for each story.

Implementation MUST follow the existing directory structure and local conventions. Artisan
generators SHOULD be used for Laravel classes when available, with `--no-interaction`.
Frontend work MUST use Wayfinder-generated route helpers for Laravel endpoints and existing
Vue/Inertia patterns. Documentation files MUST only be created when explicitly requested.

Before a story is marked complete, its affected tests MUST pass, migrations MUST run
cleanly for the feature scope, and any required environment variables MUST be reflected in
`.env.example`.

## Governance

This constitution supersedes conflicting project planning guidance. AGENTS.md, Laravel
Boost guidance, domain skills, and design standards remain authoritative where they are
more specific and do not conflict with these principles.

Amendments require a written rationale, a semantic version change, and synchronization of
the Spec Kit plan, specification, and task templates. A MAJOR version is required for
removing or redefining a principle in a way that invalidates existing delivery gates. A
MINOR version is required for adding a principle or materially expanding required checks. A
PATCH version is required for wording clarifications that do not change obligations.

Every implementation plan MUST include a Constitution Check. Any violation MUST be listed
with a business justification, a rejected simpler alternative, and explicit approval before
implementation begins. Reviewers MUST reject work that bypasses tenant isolation,
authorization, tests, or transaction-safe handling of money and stock.

**Version**: 1.0.0 | **Ratified**: 2026-05-10 | **Last Amended**: 2026-05-10
