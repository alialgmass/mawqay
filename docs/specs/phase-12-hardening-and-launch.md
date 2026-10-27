# Phase 12: Hardening and Launch

**Status:** ready-for-agent
**Prerequisite:** Phases 1–11 closed. Source: `docs/project-phases-ai-prompts.md`, Phase 12. Terms follow `docs/GLOSSARY.md`. This phase adds no product features: it makes what exists safe, fast, observable, polished and deployable for a pilot with 2–3 Vendors. Anything new that is discovered goes to a V2 backlog file.

## Problem Statement

The platform works end to end in tests, but nobody can yet trust it with real Customer money. Security rests on conventions that are only partly enforced: some resources have no Policy, the admin accounts have no second factor, there are no security headers, and the development tools are still switched on. There is no record of who approved a payment, issued a refund or paid a Vendor. Nothing is backed up or monitored, performance has never been measured against a full journey, only one Theme exists, and there is no demo data, deployment guide or launch checklist. A single failure in any of these could lose money, leak one Store's data to another, or leave the team blind during the pilot.

## Solution

Close the gaps in four groups. **Security:** every model and panel resource is covered by an enforced rule, tenant isolation is proven for every Store-owned record, admin accounts get a second factor, sensitive actions leave an audit trail, rate limits and security headers are in place, and receipts and payout proofs are never publicly reachable. **Performance and operations:** lazy loading is caught in development, the hot queries are indexed and cached under Store-prefixed keys, queues and the scheduler are configured and watched, backups run daily with a tested restore, errors are tracked, and an admin health page shows the system's pulse. **Polish:** two more Themes registered without touching core code, per-Store and per-product SEO, Arabic error pages, an accessibility and right-to-left pass, a branded admin look, and an onboarding checklist for new Vendors. **Launch:** demo data for every screen, deployment and pilot documents, and one end-to-end test that walks the whole journey from onboarding to a reconciled payout.

## User Stories

1. As a Platform Admin, I want every Store-owned record to be proven unreadable and unchangeable from another Store, so that one Vendor can never see another's data.
2. As a Platform Admin, I want an automated check that fails when code outside the admin area switches the Store filter off, so that isolation cannot be bypassed by accident.
3. As a Platform Admin, I want every model and every panel resource to be covered by a Policy or an explicit access rule, so that no screen is open by default.
4. As a Platform Admin, I want a failing check when a new resource is added without access rules, so that coverage cannot silently decay.
5. As a Platform Admin, I want a second factor on every admin login, so that a stolen password is not enough to approve payments or pay out money.
6. As a Platform Admin, I want a lockout on repeated failed logins, so that guessing passwords is impractical.
7. As a Platform Admin, I want an audit log of payment approvals and rejections, refunds, forced status changes, payout decisions, Commission changes and Store slug changes, with who, when and before and after values, so that every money-affecting decision is accountable.
8. As a Platform Admin, I want to browse and filter the audit log, so that I can investigate an incident.
9. As a Platform Admin, I want audit entries to be impossible to edit or delete, so that the trail can be trusted.
10. As a Platform Admin, I want rate limits on login, registration, Receipt upload and survey submission, so that abuse is slowed.
11. As a Platform Admin, I want security headers (transport security, framing, content-type and referrer protection, and a content security policy suited to the Storefront and the panels), so that common web attacks are blocked.
12. As a Platform Admin, I want Receipts and payout proofs to be unreachable by any public address, so that Customer and Vendor documents never leak.
13. As a Platform Admin, I want uploads checked for type, size and content, so that a malicious file cannot be stored or served.
14. As a Platform Admin, I want development tools such as the request inspector disabled or admin-gated in production, so that internal data is not exposed.
15. As a Platform Admin, I want Stores' settings, Themes, categories and product lists cached under Store-prefixed keys and refreshed when they change, so that pages are fast and never show another Store's data.
16. As a developer, I want lazy loading to fail loudly in development and tests, so that N+1 queries are found before production.
17. As a developer, I want the busiest queries indexed, so that the platform stays fast as data grows.
18. As a Platform Admin, I want queues and the scheduler configured and documented, so that notifications, expiry, funds release, surveys and Reconciliation actually run.
19. As a Platform Admin, I want a daily backup of the database and the private files, so that a loss is recoverable.
20. As a Platform Admin, I want a documented and rehearsed restore, so that I know the backup works before I need it.
21. As a Platform Admin, I want errors reported to a tracker with Store and request context, so that I hear about failures before Vendors do.
22. As a Platform Admin, I want structured logs for financial events (payment decisions, postings, refunds, payouts), so that money questions can be answered from logs.
23. As a Platform Admin, I want a health page showing queue status, last scheduler run, last Reconciliation and failed jobs, so that I can see problems at a glance.
24. As a Platform Admin, I want an external health check address that reports whether the app is up, so that an uptime monitor can watch it.
25. As a Platform Admin, I want a final review screen of every Platform Setting with its meaning and default, so that launch values are deliberate.
26. As a Vendor Owner, I want two more Themes to choose from, so that my Store can look different from the default.
27. As a Vendor Owner, I want every Theme to work on every page, including the survey and order tracking, so that my Customers never hit a broken page.
28. As a Platform team member, I want to add the two new Themes using only the register command and their own folders, so that the Theme contract is proven by use.
29. As a Customer, I want each Store and product page to have a proper title, description, share image and canonical address, so that links look right when shared and search engines understand them.
30. As a Customer, I want clear Arabic pages for not found and server errors, so that a problem is understandable.
31. As a Customer, I want every page to work right-to-left at phone, tablet and desktop widths, so that the Store is usable on any device.
32. As a Customer using assistive technology, I want pages to meet accessibility basics (labels, contrast, keyboard use), so that I can shop independently.
33. As a Vendor Owner, I want an onboarding checklist (logo, first product, Theme, shipping rates, payout details), so that I know what is left before launch.
34. As a Vendor Owner, I want the checklist to disappear when everything is done, so that my dashboard stays clean.
35. As a Platform Admin, I want both panels to carry the Platform's brand colours and font in right-to-left, so that they feel like one product.
36. As a Platform Admin, I want demo Stores with products, variants, images and one Order in every status, so that I can demonstrate every screen.
37. As a Platform Admin, I want a safe command that removes demo data and nothing else, so that demo content never mixes with real data.
38. As a Platform Admin, I want a deployment guide covering server needs, environment variables, queue and scheduler setup, build steps, zero-downtime deploys, rollback, backup and restore and a first-run checklist, so that a new server can be stood up without guesswork.
39. As the product owner, I want a pilot plan with a launch gate requiring an accountant and a lawyer to review the money-holding model, Commission invoice and Vendor terms before any real money is accepted, so that legal and financial risk is handled by people, not code.
40. As the product owner, I want a Vendor onboarding script, a test script for the first 20 real Orders, a monitoring list and an incident checklist for payment and Ledger problems, so that the pilot is run deliberately.
41. As the product owner, I want one end-to-end test that runs the whole journey from Vendor onboarding to a reconciled payout, so that a release can be trusted.
42. As a developer, I want every discovered but out-of-scope idea written to a V2 backlog, so that scope stays closed.

## Implementation Decisions

- **Carry-over fixes first.** Before the audit, close the open findings from the Phase 9, 10 and 11 reviews: payout proofs may be PDF as well as image; the payout request refuses zero or negative amounts regardless of the minimum setting; payout request and cancel check the Vendor Owner role, not just the panel; the Order page offers a survey link only for due, non-refunded, non-cancelled Orders; the survey response rate uses one date basis consistent with the averages; and report figures use a single, decided date basis (see Further Notes). Payout, Payout Details, Survey, Refund, Shipment and every other Store-owned model join the shared isolation suite.
- **Isolation suite.** A single data-driven suite enumerates every Store-owned model and every panel resource and proves, for each, that Store A cannot read or modify Store B's rows through the model scope, the Vendor Panel and (where it exists) the Storefront. A check enumerates models so a new Store-owned model without a case fails. The existing architecture test that forbids switching the Store filter off outside admin code stays and is extended to cover query-builder bypasses.
- **Access rules.** Every model and every Filament resource and page has a Policy or an explicit, tested access rule. A check enumerates resources and fails on any without one. Missing Policies are added following the existing Order and Store Policies.
- **Admin second factor.** Use the multi-factor sign-in built into the installed Filament version for Platform Admin accounts, required, with recovery codes. Vendor Owners may enable it but it is not required in this phase. No new dependency is expected.
- **Rate limits.** Existing limits on Customer authentication and surveys are kept; add or confirm limits on login, registration, Receipt upload and survey submission, each with its own named limiter and a clear Arabic message.
- **Security headers.** Add a middleware setting transport security (production only), framing denial, content-type sniffing protection, a referrer policy and a content security policy. The Storefront policy is enforced; the panels' policy starts in report-only mode because the Filament front end needs relaxed script rules, and is tightened only if it can be done without breaking the panels. The final choice of enforce or report-only for the panels is confirmed at the review.
- **File safety.** Receipts and payout proofs live on a private disk and are served only through authorised, short-lived routes (already true for Receipts; extended to proofs and verified for every private file). Uploads are validated by detected type, size and, for images, decoded and re-encoded where sensible. A test proves no private file has a public URL.
- **Development tools.** The request inspector is disabled by default outside local environments and, when enabled, gated to Platform Admins.
- **Audit log.** A single append-only audit trail (actor, role, action, subject, Store, before and after values, reason, time, request address) written by the existing actions at the points listed: payment approve and reject, refund, forced status change, payout request-settled decisions (paid and rejected), Commission rate and default changes, and Store slug changes. The existing order override record is migrated into it. Entries cannot be edited or deleted (model guard and, where supported, database triggers, as for Ledger Entries). An Admin page lists and filters it by actor, action, Store and date. Actions write the entry in the same transaction as the change.
- **Performance.** Turn on lazy-loading prevention outside production. Fix every N+1 the end-to-end run reveals. Add indexes for the hot paths: Orders by Store and status, Products and Categories by Store and slug, Ledger Entries by common lookups, Payouts and Surveys by Store and status. Cache Store settings, active Theme, Categories and product listings under Store-prefixed keys, invalidated on change, with tests that one Store never reads another's cache.
- **Queues and scheduler.** Define the queue configuration and the process supervisor setup for workers, plus the scheduler entry, in the deployment guide. Whether to adopt a queue dashboard package is a dependency decision (see Further Notes); the default is supervisor configuration only.
- **Backups.** A scheduled daily backup of the database and the private files disk, with retention and failure alerts, and a documented restore that is rehearsed once on a clean environment. The backup tool is a dependency decision (see Further Notes).
- **Error tracking and logging.** Errors are reported with Store, user role and request context, never with secrets or personal data. Financial events (payment decisions, postings, refunds, payouts, Reconciliation results) are written as structured log lines. The tracker choice is a dependency decision.
- **Health.** A lightweight public health address returns only up or down and nothing sensitive. An Admin health page shows queue backlog, last scheduler heartbeat, last Reconciliation result and age, and failed jobs. The scheduler writes a heartbeat the page reads.
- **Platform Settings review.** The settings page shows each setting with its meaning, unit, default and current value, and validates ranges.
- **Extra Themes.** Two new Themes (working names classic and minimal) are folders under the Themes directory with a manifest, following the Theme contract at its current version and including every required page and the optional ones (order detail, order confirmation, survey). They are registered only with the Theme register command. A test proves registering them changed no core code path and every page renders for each Theme. The Theme documentation is updated if the exercise reveals a gap.
- **SEO.** Per-Store and per-product title, description, share image and canonical address; Arabic not-found and server-error pages. Whether server-side rendering is feasible within the current set-up is investigated; if not, the decision and reason are documented instead.
- **Accessibility and right-to-left.** An automated accessibility check and a manual pass over every Theme page at phone, tablet and desktop widths; defects found are fixed within the Themes and shared components.
- **Vendor onboarding checklist.** A dashboard widget showing logo uploaded, first product published, Theme chosen, shipping rates set and payout details saved, hidden once all are done.
- **Panel branding.** A custom look for both panels using the Platform's colour and font tokens, verified right-to-left, built with the existing panel build.
- **Demo data.** Seeders create two demo Stores with products, variants and placeholder images, and Orders in every status (including a Refunded one, a Payout in each state and Surveys), enough to demonstrate every screen. A command removes only demo-marked data. Demo data is never created in production.
- **Documents.** A deployment guide (requirements, every environment variable with an example file, queue and scheduler setup, build steps, a zero-downtime deploy script, rollback, backup and restore, first-run checklist) and a pilot plan (launch-gate checklist owned by the product owner, Vendor onboarding steps, a script for the first 20 real Orders, what to monitor, an incident checklist for payment and Ledger issues). These are requested here, so they are in scope.
- **End-to-end journey.** One test drives the browser through: Vendor onboarding, publishing a product, Customer registration, checkout, Receipt upload, Admin approval, Vendor shipping, delivery, survey submission, funds becoming Available, payout request, Admin marking it paid, and a Reconciliation result of zero.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies; PHPUnit replaces Pest wording for backend tests.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: what each actor can see and do, what is refused, what headers and addresses a client observes, and what a full journey produces. Do not assert class structure or query shape.
- **Seam: the HTTP, panel and scheduled-command boundary, plus one browser journey (the only seams).** Backend hardening is tested through requests, panels and commands as in earlier phases. The single browser seam is the end-to-end journey, run with the repo's existing end-to-end runner. No other new seam is proposed.
- **Cases:**
  - isolation: for every Store-owned model and panel resource, cross-Store read and write attempts fail; the enumeration check fails when a new model or resource has no case; the Store-filter bypass check fails on a planted violation;
  - access: every resource and page has a rule; a guest, a Customer, a Vendor Owner and an Admin each get only what is allowed;
  - second factor: an Admin cannot reach the panel without it; recovery codes work once; lockout after repeated failures;
  - rate limits: each limited route returns the limit response after the threshold and recovers afterwards;
  - headers: every response carries the expected headers; transport security only in production; the policy does not block the Storefront or panel pages in the end-to-end run;
  - private files: no Receipt or proof has a public URL; authorised and unauthorised fetches behave correctly; uploads of disguised, oversized or wrong-type files are refused;
  - request inspector is off or admin-only in production;
  - audit: each listed action writes exactly one entry with correct before and after values and actor, in the same transaction (a failed change writes nothing); entries cannot be altered; the page filters;
  - caches: invalidation on change; no cross-Store reads;
  - lazy loading: the suite runs with prevention on and the end-to-end journey triggers none;
  - health: the public address reveals nothing sensitive; the Admin page reflects a missing heartbeat, a failed job and a failing Reconciliation;
  - backup: the scheduled job produces an archive including the private disk and alerts on failure; the restore procedure is rehearsed manually and recorded;
  - Themes: both new Themes register by command only, render every page, and fall back correctly if one is deactivated;
  - SEO and error pages: tags and canonical addresses present and correct per Store and product; Arabic error pages render; no cross-Store leakage in tags;
  - onboarding checklist appears, ticks as steps complete and disappears when all are done;
  - demo seeding creates every status and the wipe command removes only demo data and refuses to run in production;
  - the end-to-end journey passes and ends with zero Reconciliation differences.
- **Prior art.** The Phase 1 isolation helper and architecture test, Phase 7–11 panel and notification tests, the Phase 8 and 10 Reconciliation tests, the Theme rendering tests, and the existing end-to-end tests for the admin sign-in.
- **Gate.** PHPUnit, formatting, front-end type check, lint and build pass; the end-to-end journey passes against a freshly seeded environment.

## Out of Scope

- New product features of any kind; ideas found go to the V2 backlog.
- Online payment, courier gateways, custom domains, Vendor staff and self-registration (V2).
- Public product reviews, coupons and a page builder (V2).
- Multi-region or high-availability infrastructure, and autoscaling.
- Legal, tax and accounting work itself: the pilot plan requires it before real money, but it is done by people, not by this phase.
- Penetration testing by an outside party (recommended before wide launch, not part of this phase).

## Further Notes

- **Tracker.** The repo has no remote tracker configured, so, as in earlier phases, this spec lives in `docs/specs/` and tickets go to `.scratch/phase-12-hardening-and-launch/issues/` as `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real one.
- **Seams to confirm.** The HTTP, panel and scheduled-command boundary plus one browser journey. The phase plan names Pest browser tests, Dusk or Playwright; the repo already has a local end-to-end runner with sample tests, which this spec uses instead of adding a new tool. Confirm that is acceptable.
- **Dependency decisions needing your approval** (the project rule is no new dependencies without approval): a backup package (the phase plan suggests spatie/laravel-backup; the alternative is a documented dump script and scheduled command), an error tracker (Sentry or equivalent; the alternative is structured logging only), a queue dashboard (Horizon; the default here is supervisor configuration only), and an accessibility checker (axe or Lighthouse; the alternative is a manual pass). Say which you approve; the spec assumes none are added until you do.
- **Decision still open from Phase 10.** Report figures (GMV, top products, survey rating averages) are bucketed by Order creation date. If they should follow payment date instead, a payment date must be recorded on the Order first. The default here is to keep creation date, documented, unless you say otherwise.
- **Suggested ticket order.** Carry-over fixes; isolation suite and access-rule enumeration; audit log; admin second factor and lockout; rate limits and security headers; file and upload safety and request inspector gating; performance (lazy loading, indexes, caches); health page and heartbeat; queues, scheduler and backups; error tracking and structured logging; Platform Settings review; two Themes; SEO and error pages; accessibility and right-to-left pass; onboarding checklist and panel branding; demo data and wipe command; deployment guide; pilot plan; end-to-end journey; phase gate.
- **Open assumptions.** The panels' content security policy starts in report-only mode; Vendor Owners are not forced to use a second factor yet; the audit log replaces the Phase 9 order override record; demo data is marked so it can be removed safely; server-side rendering is investigated, not committed.
- **Dependency.** Phases 1–11 are complete and committed (`9ba4ba0`), so this phase can start.
