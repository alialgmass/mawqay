---
status: ready-for-agent
phase: 1
source: docs/project-phases-ai-prompts.md (Phase 1), docs/REVIEW-ROUND-1.md
---

# Phase 1: Foundation

## Problem Statement

The Platform has a stripped, empty Laravel base and no tenancy. Nothing yet lets the Platform Admin, a Vendor Owner or a Customer reach the right surface, and nothing prevents one Store's data from leaking to another. Every later phase (catalog, orders, payments, ledger) depends on a trustworthy Store boundary and on three separate entry points, so that boundary has to exist and be proven before anything is built on top of it.

## Solution

Stand up the skeleton that every later phase builds on. The Platform Admin signs in at the Admin Panel and sees all Stores. A Vendor Owner signs in at the Vendor Panel and is confined to exactly one Store. A visitor opening `/{store-slug}` lands on that Store's Storefront shell, and an unknown or reserved slug is a 404. Every Store-owned record is automatically scoped to its Store, and a reusable isolation test proves that Store A can never read or change Store B's data. A CI pipeline runs formatting, static analysis and the tests on every change.

## User Stories

1. As a Platform Admin, I want to sign in to the Admin Panel, so that I can manage the Platform.
2. As a Platform Admin, I want the Admin Panel to be closed to everyone who is not a Platform Admin, so that Vendors and Customers can never reach platform controls.
3. As a Platform Admin, I want to see a list of all Stores and open any one of them read-only, so that I can confirm tenancy is working across Stores.
4. As a Platform Admin, I want the Admin Panel in Arabic and right-to-left, so that it matches how my team works.
5. As a Platform Admin, I want a deliberate, single way to look across all Stores, so that cross-Store access never happens by accident.
6. As a Vendor Owner, I want to sign in to the Vendor Panel, so that I can run my Store.
7. As a Vendor Owner, I want the Vendor Panel to show only my own Store, so that I never see another Vendor's data.
8. As a Vendor Owner, I want to be refused when I try to open another Store's Vendor Panel address, so that my competitors cannot be browsed.
9. As a Vendor Owner, I want to belong to exactly one Store and not see a Store switcher, so that I am never confused about which shop I am editing.
10. As a Vendor Owner, I want the Vendor Panel in Arabic and right-to-left, so that I can use it comfortably.
11. As a Vendor Owner, I want to be refused at the Admin Panel, so that the two panels stay separate.
12. As a Customer, I want to open `/{store-slug}` and see that Store's Storefront, so that I know I am in the right shop.
13. As a Customer, I want an unknown Store Slug to show a clear not-found page, so that I know the shop does not exist.
14. As a Customer, I want a Reserved Slug such as `admin` or `login` never to open a Storefront, so that Platform addresses are never confused with Stores.
15. As a Customer, I want the Storefront shell to be right-to-left, in Arabic and mobile first, so that it is readable on my phone.
16. As a Customer, I want the Storefront to show the Store's name, so that I can confirm which Store I am on.
17. As a Customer, I want my account to belong to one Store only, so that two Stores never share my details.
18. As a Customer, I want the same email or phone to be usable in two different Stores as two separate accounts, so that registering in one Store does not block me in another.
19. As a Customer, I want my login to be accepted only inside the Store I registered in, so that my session cannot be used elsewhere.
20. As a Platform Admin, I want a Store Slug to be 3 to 30 characters of lowercase letters, digits and hyphens, so that addresses stay clean and predictable.
21. As a Platform Admin, I want a Store Slug to be unique across the Platform, so that no two Stores share an address.
22. As a Platform Admin, I want Reserved Slugs to be a configurable list covering `admin`, `vendor`, `api`, `login`, `register`, `logout`, `storage`, `assets`, `build`, `up`, `livewire` and `filament`, so that future Platform routes never collide with a Store.
23. As a Platform Admin, I want an automated check that fails if a Reserved Slug is ever accepted as a Store Slug, so that this protection cannot silently regress.
24. As a Platform Admin, I want a Store to carry a status of Draft, Active or Suspended from the start, so that later phases can gate public access without reshaping the data.
25. As a Platform Admin, I want a Store to hold its name, slug, status, contact details, optional commission rate and optional Theme, so that later phases only fill in behaviour.
26. As a Platform Admin, I want a demo Platform Admin, a demo Store and a demo Vendor Owner in local seed data, so that I can try the panels immediately.
27. As a Platform Admin, I want the demo seed data to never run in production, so that no test accounts exist on the live Platform.
28. As a developer, I want every Store-owned record to be filtered to the current Store automatically, so that I cannot forget a filter on any query.
29. As a developer, I want a Store-owned record to receive its Store automatically when created, so that I cannot create orphaned or mis-assigned data.
30. As a developer, I want the current Store to be resolved from the address in one place, behind a swappable resolver, so that custom domains can be added later without touching the rest of the code.
31. As a developer, I want the Vendor Panel to treat the Store as its tenant and also apply the automatic scope, so that tenancy is enforced in two independent layers.
32. As a developer, I want disabling the Store scope to be possible only through one explicit helper that is used only in Admin Panel code, so that it is auditable.
33. As a developer, I want an automated check that fails if code outside the Admin Panel disables the Store scope, so that the isolation rule is enforced, not just documented.
34. As a developer, I want a money value stored as whole piasters with EGP formatting, so that no later phase ever uses floating-point money.
35. As a developer, I want a reusable isolation test helper, so that every later phase can prove cross-Store separation with a few lines.
36. As a developer, I want each phase to add its own isolation cases to that helper, so that coverage grows with the product.
37. As a developer, I want feature code organised in modules with one-way dependencies (Core, then Tenancy, then Auth, Storefront and Backoffice), so that later phases stay decoupled.
38. As a developer, I want each module to contribute its own Filament resources to the panels through a plugin, so that panels stay modular.
39. As a developer, I want one working example of that plugin pattern, a read-only Store list in the Admin Panel, so that later modules copy a proven shape.
40. As a developer, I want queue jobs to carry their Store explicitly, cache keys to be prefixed by Store, and files to live under a per-Store folder, so that isolation extends beyond the database.
41. As a developer, I want a single command or workflow to run formatting, static analysis and tests, so that quality gates are the same locally and in CI.
42. As a developer, I want CI to fail the build if formatting, static analysis or any test fails, so that broken code never reaches the main line.
43. As a developer, I want the install and run steps (migrate, seed, front-end dev server, queue) documented in the delivery summary, so that anyone can bring the project up.
44. As a Platform Admin, I want a policies and gates skeleton in place for admins, Vendor Owners and Customers, so that later phases only add rules instead of inventing the mechanism.
45. As a Customer, I want login and registration to be throttled, so that my account is protected from guessing, even though the screens arrive in a later phase.

## Implementation Decisions

- **Modules.** Build five modules in this phase: Core, Tenancy, Auth, Storefront and Backoffice. Dependency direction is one-way: Core is the base; Tenancy depends on Core; Auth, Storefront and Backoffice depend on Tenancy and Core. Cross-module communication uses Events and Contracts, never upward relations. The installed `nwidart/laravel-modules` major is compatible with the installed Laravel major (the base boots with both), so no compatibility spike is needed.
- **Core.** Provides the Store-ownership concern with its automatic scope (filters by the current Store or by the Vendor Panel's tenant, and assigns the Store on create), a Money value object holding integer piasters with EGP formatting, the single explicit helper for running code without the Store scope, and shared test helpers.
- **Tenancy.** Owns the Store: unique slug, name, status (Draft, Active, Suspended), optional Theme reference, optional Theme Settings, optional logo, optional commission rate, and contact fields. Owns the swappable Store resolver contract with a path-based implementation now, middleware that loads the Store from the `{store:slug}` route parameter and exposes the current Store, and the config-driven Reserved Slug list with a validation rule (3 to 30 characters, `a-z`, `0-9`, hyphen, not reserved, unique).
- **Auth.** Extends the user with a role (Platform Admin or Vendor Owner) and an optional Store. Following the phase plan, role is a plain attribute on the user. The base's current Spatie-role check for the admin gate is replaced by this attribute for panel access. Adds the Customer record (Store, name, email, phone, password) with uniqueness per Store and a separate `customer` authentication guard with no screens yet. Adds a policies and gates skeleton. Customer login and registration throttling is declared now so later screens inherit it.
- **Storefront.** A route group `/{store:slug}` whose home page shows the Store name in a right-to-left, `lang="ar"`, mobile-first layout, with a not-found page for unknown Stores. The layout exposes a CSS-variable token set (primary, secondary, background, surface, text, muted text, border, medium radius, body and heading fonts) with sensible defaults. The Inertia + Vue + Tailwind setup already in the base is reused, and Vite is set to load module assets.
- **Backoffice.** Holds only panel providers and configuration. Two Filament panels: the Admin Panel at `/admin` (Platform Admin only) and the Vendor Panel at `/vendor` (Vendor Owner only). The Vendor Panel uses the Store as tenant, identified by slug, with the tenant switcher hidden, because a Vendor Owner belongs to exactly one Store. Both panels are Arabic and right-to-left.
- **Plugin pattern.** Each module may provide a Filament plugin that the panel providers register. Tenancy ships a read-only Store resource (list and view) for the Admin Panel as the reference example.
- **Isolation rules.** Single database, a Store reference on every Store-owned table. Queue jobs carry their Store explicitly; cache keys use the prefix `store:{id}:`; uploaded files live under `stores/{id}/`. The scope may be disabled only through the one helper, and only from Admin Panel code.
- **Seeding.** One Platform Admin and one demo Store with its Vendor Owner, seeded in local environments only.
- **Documents the phase plan wins on.** Where the PRD and the phase plan disagree, the phase plan applies (see ADR 0001).
- **CI.** A pipeline that installs dependencies, builds the front end, checks formatting, runs static analysis and runs the tests.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: given this request or this signed-in actor, what response or visible data results. Do not assert how the scope, resolver or middleware are implemented internally.
- **Seam 1: the HTTP boundary.** Requests to `/{store-slug}`, `/admin` and `/vendor`, plus Filament panel access, driven as feature tests. Cases:
  - a valid slug loads the correct Store, and an unknown or reserved slug returns 404;
  - the Admin Panel admits only Platform Admins, and the Vendor Panel admits only Vendor Owners;
  - a Vendor Owner of Store A cannot reach Store B through the panel;
  - the slug rules (length, characters, uniqueness, reserved) are enforced;
  - a Store-owned record created through a request receives its Store automatically;
  - the same email or phone registered in two Stores yields two independent Customers;
  - a Customer session from Store A is not valid in Store B.
- **Seam 2: the isolation helper.** One reusable test helper (an abstract case or trait) that, given a Store-owned model, creates data for two Stores and asserts that Store A cannot read, list, count or modify Store B's rows. Every later phase adds its models to it. This is the only new seam proposed.
- **Architecture check.** An automated test that fails if code outside the Admin Panel calls the scope-disabling helper or the underlying framework call, and a test that every Reserved Slug is rejected.
- **Framework.** PHPUnit classes, with no new test dependency, matching what is installed and the repo conventions. The docs' wording of Pest is superseded for this repo. If Pest is later approved, tests convert mechanically.
- **Prior art.** The base already ships an example feature test and an example unit test, plus e2e smoke tests for the admin login. These are the only templates; there is no earlier tenancy test to follow.
- **Gate.** Formatting, static analysis and the tests must all pass before the phase closes.

## Out of Scope

- Creating Stores or Vendor Owners through a UI, invitations and branding (Phase 2).
- Catalog, products, Themes, customer screens, carts, orders, payments, ledger, shipping, surveys and notifications (later phases).
- Customer login and registration screens (Phase 5). Only the guard and the data shape exist now.
- Custom domains and subdomains.
- Real data migration from the previous product. The base starts from an empty schema.
- Two-factor enforcement for admins, audit logging and security headers (Phase 12).

## Further Notes

- **Dependencies need your approval before this phase can fully pass its own gate.** The phase plan asks for static analysis (Larastan) and names Pest. Neither is installed. This spec uses PHPUnit. Larastan would be a new dev dependency, so either approve adding it or drop static analysis from this phase's gate.
- The existing base `User` currently gates the Admin Panel with a Spatie role check. Replacing it with the role attribute is a deliberate change here. The Spatie permission package stays installed and is unused until needed.
- The existing `.sqlite` files still hold the old product's schema. A fresh migration is expected before any manual run.
- Glossary terms used here (Store, Vendor Owner, Platform Admin, Customer, Store Slug, Reserved Slug, Store Status, Admin Panel, Vendor Panel, Storefront) are defined in `docs/GLOSSARY.md`.
- Definition of Done from the phase plan: `/admin` admits only the Platform Admin and `/vendor` admits only a Vendor Owner, with Store A's owner unable to enter Store B; `/{slug}` loads the correct Store while an unknown or reserved slug returns 404; the isolation tests and CI pass.
