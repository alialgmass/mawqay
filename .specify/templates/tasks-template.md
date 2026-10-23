---
description: "Task list template for Laravel/Inertia POS SaaS feature implementation"
---

# Tasks: [FEATURE NAME]

**Input**: Design documents from `/specs/[###-feature-name]/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are mandatory. Include focused PHPUnit unit/feature/integration tests for every behavior change, and include frontend/type checks when Vue or TypeScript changes.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel because it touches different files and has no dependency on another unfinished task
- **[Story]**: Which user story this task belongs to, such as US1, US2, or US3
- Include exact file paths in descriptions

## Path Conventions

- Laravel backend: `app/`, `routes/`, `database/`, `config/`
- Inertia/Vue frontend: `resources/js/pages/`, `resources/js/components/`, `resources/js/composables/`, `resources/js/stores/`
- Blade/print views: `resources/views/`
- Tests: `tests/Feature/`, `tests/Unit/`

<!--
  Replace all sample tasks below with concrete tasks generated from:
  - prioritized user stories in spec.md
  - Constitution Check in plan.md
  - entities in data-model.md
  - endpoints/contracts
  - tenant, permission, audit, and operational UX requirements
-->

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Establish files, routes, migrations, permissions, and contracts shared by all stories.

- [ ] T001 Confirm affected routes and generated Wayfinder actions/routes for [FEATURE]
- [ ] T002 Create or update migrations in `database/migrations/`
- [ ] T003 [P] Create or update enums/value objects in `app/Enums/`
- [ ] T004 [P] Create or update permission seed data in `database/seeders/`
- [ ] T005 [P] Create or update factories in `database/factories/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before any user story implementation.

**CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T006 Implement tenant-scoped models and relationships in `app/Models/`
- [ ] T007 Implement Repository classes in `app/Repositories/`
- [ ] T008 Implement shared Action classes in `app/Actions/`
- [ ] T009 Implement policies/gates/permissions in `app/Policies/` or `app/Providers/`
- [ ] T010 Add cross-tenant isolation tests in `tests/Feature/`
- [ ] T011 Add permission failure tests in `tests/Feature/`

**Checkpoint**: Tenant isolation, authorization, data access, and shared actions are ready.

---

## Phase 3: User Story 1 - [Title] (Priority: P1) MVP

**Goal**: [Brief description of what this story delivers.]

**Independent Test**: [How to verify this story works on its own.]

### Tests for User Story 1

> Write these tests before implementation and verify they fail for the missing behavior.

- [ ] T012 [P] [US1] Unit test for [Action] in `tests/Unit/`
- [ ] T013 [P] [US1] Feature test for [route/journey] in `tests/Feature/`
- [ ] T014 [P] [US1] Validation and authorization failure test in `tests/Feature/`

### Implementation for User Story 1

- [ ] T015 [US1] Implement [Action] in `app/Actions/`
- [ ] T016 [US1] Implement [Repository] in `app/Repositories/`
- [ ] T017 [US1] Implement controller/page endpoint in `app/Http/Controllers/`
- [ ] T018 [US1] Implement Vue/Inertia page or component in `resources/js/`
- [ ] T019 [US1] Add loading, error, responsive, and RTL states where applicable
- [ ] T020 [US1] Run focused tests: `php artisan test --compact --filter=[testName]`

**Checkpoint**: User Story 1 is fully functional and independently testable.

---

## Phase 4: User Story 2 - [Title] (Priority: P2)

**Goal**: [Brief description of what this story delivers.]

**Independent Test**: [How to verify this story works on its own.]

### Tests for User Story 2

- [ ] T021 [P] [US2] Unit test for [Action] in `tests/Unit/`
- [ ] T022 [P] [US2] Feature test for [route/journey] in `tests/Feature/`
- [ ] T023 [P] [US2] Edge case test for [tenant/permission/data integrity case]

### Implementation for User Story 2

- [ ] T024 [US2] Implement [Action] in `app/Actions/`
- [ ] T025 [US2] Implement [Repository] in `app/Repositories/`
- [ ] T026 [US2] Implement controller/page endpoint in `app/Http/Controllers/`
- [ ] T027 [US2] Implement Vue/Inertia page or component in `resources/js/`
- [ ] T028 [US2] Run focused tests: `php artisan test --compact --filter=[testName]`

**Checkpoint**: User Stories 1 and 2 work independently.

---

## Phase 5: User Story 3 - [Title] (Priority: P3)

**Goal**: [Brief description of what this story delivers.]

**Independent Test**: [How to verify this story works on its own.]

### Tests for User Story 3

- [ ] T029 [P] [US3] Unit test for [Action] in `tests/Unit/`
- [ ] T030 [P] [US3] Feature test for [route/journey] in `tests/Feature/`

### Implementation for User Story 3

- [ ] T031 [US3] Implement [Action] in `app/Actions/`
- [ ] T032 [US3] Implement [Repository] in `app/Repositories/`
- [ ] T033 [US3] Implement controller/page endpoint in `app/Http/Controllers/`
- [ ] T034 [US3] Implement Vue/Inertia page or component in `resources/js/`
- [ ] T035 [US3] Run focused tests: `php artisan test --compact --filter=[testName]`

**Checkpoint**: All selected user stories are independently functional.

---

[Add more user story phases as needed.]

---

## Phase N: Polish & Cross-Cutting Concerns

**Purpose**: Improvements and verification that affect multiple stories.

- [ ] TXXX [P] Add or update audit/event/notification handling in `app/`
- [ ] TXXX [P] Add report/cache invalidation or queued job coverage where applicable
- [ ] TXXX [P] Run frontend checks if resources changed: `npm run types:check`
- [ ] TXXX Run PHP formatting if PHP changed: `vendor/bin/pint --dirty --format agent`
- [ ] TXXX Run final focused test command: `php artisan test --compact [path-or-filter]`
- [ ] TXXX Verify migrations for feature scope

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies; can start immediately
- **Foundational (Phase 2)**: Depends on setup; blocks all user stories
- **User Stories (Phase 3+)**: Depend on foundational completion
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### Within Each User Story

- Tests before implementation
- Models and migrations before repositories
- Repositories before actions that depend on them
- Actions before controllers/pages
- Backend routes before Wayfinder frontend calls
- Story complete before moving to lower-priority work unless explicitly parallelized

### Parallel Opportunities

- Tasks marked [P] can run in parallel when they touch different files
- Different user stories can be developed in parallel after foundational tasks are complete
- Tests for independent Actions can be authored in parallel

---

## Implementation Strategy

### MVP First

1. Complete Setup.
2. Complete Foundational tenant, permission, repository, and action work.
3. Complete User Story 1.
4. Run focused tests and validate User Story 1 independently.
5. Continue by priority.

### Quality Gates

- No story is complete without passing focused automated tests.
- No PHP story is complete without Pint when PHP files changed.
- No Vue/TypeScript story is complete without relevant type/lint checks.
- No tenant-owned story is complete without cross-tenant isolation coverage.
