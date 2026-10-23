# Documentation Review — Round 1

Scope: the PRD and the 12-phase plan. No application code, migrations or implementation were touched. The phase plan wins whenever it conflicts with the PRD. The glossary is `GLOSSARY.md`; decision records are in `adr/`.

## Canonical locations

| Artifact | Location |
| --- | --- |
| PRD | `docs/PRD — Multi-Tenant Modest Fashion Commerce Platform.md` |
| Phase plan (wins on conflict) | `docs/project-phases-ai-prompts.md` |
| Glossary | `docs/GLOSSARY.md` |
| Decision records | `docs/adr/` |
| This review | `docs/REVIEW-ROUND-1.md` |

The repo-root `CONTEXT.md`, `CLAUDE.md` and `AGENTS.md` describe a different product (THOB). They are not used as input here.

## Decisions and traceability

| ID | Decision | Status | PRD sections changed | Phases affected | Acceptance criteria touched |
| --- | --- | --- | --- | --- | --- |
| R1-1 | Phase plan wins. Storefront is Inertia + Vue themes. `/admin` and `/vendor` are Filament, each module exposing a Filament Plugin. | Decided (ADR 0001) | §1, D2 (§4), §5 `Backoffice` row and rules, D8 and design-system rules (§14) | 1, 2, 3 (all panel work) | Phase 1 DoD (panel access) |
| R1-2 | English canonical terms with Arabic labels. Store ≠ Vendor. | Decided | none (new glossary) | all | none |
| R1-3 | Customer accounts are per store. Identifier is email OR phone, at least one, unique per store. OTP deferred. | Decided | U1 (§6), §13 | 5 | Phase 5 DoD (same email in two stores) |
| R1-4 | Commission: 10% default, per-store override, product lines only, frozen per item, round half-up per item, no per-transaction minimum. | Decided | §13 | 6 (snapshot), 8 (ledger) | Phase 6 DoD (frozen commission), Phase 8 DoD (worked example) |
| R1-5 | Vendor sets and keeps 100% of shipping fees and arranges the courier. Platform never pays for shipping. | Decided | §13 | 6 (rates), 8, 9 | Phase 8 DoD (1,140 payable includes 60 shipping) |
| R1-6 | Vendors are created by admin invitation only. Self-signup is V2. | Decided | §13 | 2 | Phase 2 DoD |
| R1-7 | One cart, order and active payment belong to exactly one store. | Decided | none (already implied) | 6, 7 | Phase 6 isolation tests |
| R1-8 | The reservation timer pauses during `payment_review`. Rejection starts a new shorter window, not a fresh 24h. | Decided in principle (ADR 0002, proposed). Window length unresolved (OQ-2) | P4 (§6), flow ج (§7) | 6 (`orders:expire`), 7 (approve/reject) | Phase 6 DoD (auto-cancel after 24h) now applies to `pending_payment` only |

## Open questions (updated in Round 2: see below)

- **OQ-1: payment attempt limit.** Does "3 attempts" mean 3 total Receipt submissions, or 3 retries after the first (4 total)? Marked in PRD P2 and Phase 7. Blocks the Phase 7 attempt counter, its test, and the customer-facing message.
- **OQ-2: post-rejection window.** Is it a fixed 6 hours or a `platform_settings` value? 6 hours was only a proposal. Marked in PRD P4 and Phase 7. If configurable, it needs a new setting in Phase 2 alongside `stock_reservation_hours`.

## Remaining contradictions and stale text

1. PRD §12 roadmap (6 phases, 13.5 weeks) vs the phase plan (12 phases, about 19 weeks). The PRD roadmap was not rewritten.
2. Reserved slugs differ. The PRD D1 list is `admin, vendor, api, login, register, storage, assets, up`. The phase plan adds `logout, build, livewire, filament`. Pick one list.
3. PRD §8 still says shipping goes to the vendor "(open assumption in §13)". That assumption is now decided (R1-5), so the parenthetical is stale.
4. PRD §14 component tables and the Storybook rule still mention "dashboards"/"اللوحات" as consumers, although they now apply to the Storefront only.
5. PRD §11 and §13 say Pest. The repo-level instructions say PHPUnit. Out of scope for this review, but one test framework must be chosen before Phase 1.
6. Slug immutability is "proposed" in PRD §13 but stated as fixed in D1 and the glossary.
7. Repo-root `CONTEXT.md` defines "Customer", "Supplier" and a B2B vocabulary that conflicts with this product's glossary. Needs a decision on where each product's docs live.

## Round 2 dependencies

- Resolve OQ-1 and OQ-2 first. They gate Phase 7 and, for OQ-2, a Phase 2 setting.
- Refund and return policy (PRD §13): reasons, window, who bears return shipping, whether a refund after `completed` is allowed. Gates Phase 9 and the Ledger reversal rules.
- When the Return Window starts: Vendor marks Delivered, or Customer confirms receipt. Affects `completed` and Available funds.
- Ledger and payout details: minimum payout, whether payout can partially cover Available, payout failure handling.
- Theme contract versioning and who may change it.
- Isolation rule: where `withoutGlobalScopes` is permitted (admin code only) and how it is enforced.
- Legal and tax status of the Platform holding vendor money. Not a code question, but a launch prerequisite.
- Whether the `payment_review` pause needs a ceiling (the SLA is 4h, but nothing stops an admin from leaving stock locked indefinitely).

## Round 2 (applied)

Accepted via "next step" as the recommended answers. OQ-1 and OQ-2 are therefore **provisional**; revert them if that was not intended.

| ID | Decision | PRD / phase changes |
| --- | --- | --- |
| R2-1 (OQ-1) | Three Receipt submissions in total; the third rejection is final; admin can still cancel. | P2, §13; Phase 7 |
| R2-2 (OQ-2) | Post-rejection window is the setting `payment_retry_window_hours`, default 6. | P4, flow ج, §13; Phase 2 settings, Phase 7 |
| R2-3 | Review pause never auto-cancels; alert at the 4h SLA, "overdue" flag at 72h (`payment_review_overdue_hours`). | P4; Phase 2, Phase 7 widget |
| R2-4 | Return Window starts at the first of vendor Delivered / customer confirmation; 7 days; `delivered_at` never overwritten. | §13; Phase 9 |
| R2-5 | Refunds are admin-only, no right of return or goods-return tracking, fixed reason list, override reason after `completed`. | §13; Phase 9; glossary |
| R2-6 | Slug fixed after launch; admin change leaves a permanent 301. | §13 |
| R2-7 | Reserved slugs are the union of both lists, in config, with an automated test. | D1 |
| R2-8 | Pest is the test framework per the docs. The repo CLAUDE.md still says PHPUnit (not touched). | none |
| R2-9 | PRD §12 roadmap replaced by the 12 phases (about 19 weeks). | §12 |
| R2-10 | Payouts: any amount between `min_payout_amount` (default 500 EGP) and Available; one open request per store; rejection restores Available. | Phase 2 settings, Phase 10 |
| R2-11 | Docs live under `docs/`; repo-root `CONTEXT.md` stays THOB's separate glossary. | none |

### Resolved contradictions
Roadmap (1), reserved slugs (2), stale shipping parenthetical (3), slug status (6) from Round 1.

### Still open
- Contradiction 4: PRD §14 component tables still reference dashboards.
- Contradiction 5: CLAUDE.md says PHPUnit, the docs say Pest.
- Contradiction 7: THOB docs at the repo root vs this product's docs.

### Round 3 candidates
- Theme contract versioning and who may change it.
- Survey timing relative to the Return Window.
- Phase 6 DoD still says "auto-cancels after 24h": confirm wording now that `payment_review` is exempt.
- Legal and tax status of the Platform holding Vendor money (launch prerequisite, not a docs decision).

## Round 3 (applied)

Accepted via "next step" as the recommended answers.

| ID | Decision | PRD / phase changes |
| --- | --- | --- |
| R3-1 | Theme `contract_version` is semver; minor = additive, major = breaking; themes declare a target; platform supports current and previous major; unsupported falls back to `default`; only the platform team changes the contract. | §9; Phase 4 |
| R3-2 | Survey goes out `survey_delay_days` after `delivered_at`, independent of the Return Window; a Low Rating alerts only and never refunds or changes status. | F3; Phase 11 |
| R3-3 | Auto-cancel applies to `pending_payment` only; `payment_review` is exempt. | C4; Phase 6 DoD |
| R3-4 | PRD §14 is explicitly Storefront-only; panels are Filament with a custom theme. Nothing deleted. | §14 |
| R3-5 | Suspended Store: no cart additions, checkout or payouts; in-flight orders continue; funds keep maturing; payouts resume on reactivation. | T3; Phase 2, Phase 10; glossary |
| R3-6 | Launch gate: no real customer money until an accountant and a lawyer review the model. Owner: product owner. | §13; Phase 12 pilot plan |
| R3-7 | Repo-root `CONTEXT.md`, `CLAUDE.md` and `AGENTS.md` are untouched and out of scope here. They describe THOB. The Mawqaay docs ignore existing code logic by instruction. | none |

### Final state
- All Round 1-3 questions are decided. OQ-1 and OQ-2 remain provisional (accepted by "next step").
- Contradiction 4 (PRD §14) is resolved.
- Contradiction 5 (Pest vs PHPUnit) and 7 (THOB docs at the repo root) are intentionally left for the product owner before implementation.
