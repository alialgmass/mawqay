# Phase 2: Stores & Identity

**Status:** ready-for-agent
**Prerequisite:** Phase 1 (Foundation) closed. Source: `docs/project-phases-ai-prompts.md`, Phase 2. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

Today a Store can only exist if a developer seeds it. A Platform Admin cannot onboard a Vendor, a Vendor Owner cannot receive access or make the Store look like their brand, and the Storefront shows only a bare name. Nobody can pause a misbehaving Store, rename a Store's address safely, or tune the Platform-wide rules (default Commission Rate, reservation hours, return window) without a code change.

## Solution

A Platform Admin onboards a Vendor in one step from the Admin Panel: the Store is created in Draft with its Vendor Owner, and the owner receives an Arabic email with a time-limited invitation link to set a password. The owner lands in the Vendor Panel, fills in the Store's identity (logo, favicon, colours, contact details, social links, footer text) and sees their Store status. The Platform Admin activates the Store when ready, can suspend and reactivate it, and can change its slug with a permanent redirect from the old one. The Storefront wears the Store's identity, shows a maintenance page when suspended, and hides Draft Stores from the public. Platform-wide rules live in a settings page the Platform Admin edits.

## User Stories

1. As a Platform Admin, I want to create a Store and its Vendor Owner in a single form, so that onboarding a Vendor takes one step.
2. As a Platform Admin, I want the new Store to start in Draft, so that nothing is public before the Vendor is ready.
3. As a Platform Admin, I want the Store creation to fail as a whole if any part fails, so that I never end up with a Store without an owner or an owner without a Store.
4. As a Platform Admin, I want live validation of the Store Slug while I type, so that I learn immediately if it is too short, malformed, reserved or taken.
5. As a Platform Admin, I want to optionally set a per-Store Commission Rate when creating a Store, so that negotiated deals are honoured from day one.
6. As a Platform Admin, I want to enter the owner's name, email and mobile number, so that the Vendor can be contacted and invited.
7. As a Platform Admin, I want the system to reject an owner email already used by another user, so that one person cannot silently own two accounts.
8. As a Vendor Owner, I want to receive an Arabic invitation email, so that I understand what it is and what to do next.
9. As a Vendor Owner, I want the invitation link to let me set my own password, so that no one else ever knows it.
10. As a Vendor Owner, I want to be signed in to the Vendor Panel right after I set my password, so that I can start working at once.
11. As a Vendor Owner, I want the invitation to expire after 72 hours, so that a forgotten email does not stay a live key forever.
12. As a Vendor Owner, I want a used invitation to stop working, so that the link cannot be replayed by someone who finds the email.
13. As a Vendor Owner, I want an expired or invalid link to explain what happened in Arabic, so that I know to ask for a new one.
14. As a Platform Admin, I want to resend an invitation, so that a Vendor who missed or lost the email can still get in.
15. As a Platform Admin, I want resending to invalidate the previous link, so that only the newest link works.
16. As a Platform Admin, I want to be unable to resend once the owner has accepted, so that I do not hand out a second way into an active account.
17. As a Platform Admin, I want a list of all Stores with a status badge and a status filter, so that I can see who is Draft, Active or Suspended.
18. As a Platform Admin, I want to see a Store's owner on its page, so that I know who runs it.
19. As a Platform Admin, I want to edit a Store's name and commission override, so that I can correct details after creation.
20. As a Platform Admin, I want to activate a Draft Store, so that it becomes public when the Vendor is ready.
21. As a Platform Admin, I want to suspend a Store, so that I can stop new sales from a Vendor who breaks the rules.
22. As a Platform Admin, I want to reactivate a Suspended Store, so that a resolved issue restores normal trading.
23. As a Platform Admin, I want to be unable to put a Store into a state the flow does not allow, so that status stays meaningful.
24. As a Platform Admin, I want to change a Store Slug, so that I can fix a typo or rebrand.
25. As a Platform Admin, I want the old address to keep working by redirecting permanently to the new one, so that links already shared do not break.
26. As a Platform Admin, I want a slug change to be refused if the new slug is invalid, reserved, taken, or currently used as someone's old address, so that addresses never become ambiguous.
27. As a Platform Admin, I want to edit the Platform Settings in one page, so that I can tune Platform rules without a developer.
28. As a Platform Admin, I want sensible defaults already in place (10% Commission, 24-hour reservation, 7-day return window, 2-day survey delay, 4-hour review SLA, 72-hour overdue threshold, 6-hour retry window, 500 EGP minimum payout), so that the Platform works from the first day.
29. As a Platform Admin, I want invalid settings (negative numbers, percentages above 100) rejected, so that I cannot break pricing by typo.
30. As a Platform Admin, I want a settings change to take effect immediately despite caching, so that what I save is what the Platform uses.
31. As a Vendor Owner, I want to upload my Store logo, so that my brand appears on my Storefront.
32. As a Vendor Owner, I want to upload a favicon, so that my browser tab shows my brand.
33. As a Vendor Owner, I want uploaded files kept under my Store's own folder, so that they are isolated from other Stores.
34. As a Vendor Owner, I want to choose a primary and a secondary colour with colour pickers, so that the Storefront matches my brand.
35. As a Vendor Owner, I want colours validated as proper hex values, so that I cannot save something that breaks the page.
36. As a Vendor Owner, I want a warning when my primary colour gives poor contrast with text, so that I do not publish an unreadable Store.
37. As a Vendor Owner, I want the Storefront to automatically use black or white text on my primary colour, whichever is more readable, so that buttons stay legible.
38. As a Vendor Owner, I want to set contact email, phone and WhatsApp, so that customers can reach me.
39. As a Vendor Owner, I want to add social links, so that customers can find my social pages.
40. As a Vendor Owner, I want to write the footer text, so that my Storefront carries my own message.
41. As a Vendor Owner, I want to record my shipping origin as free text, so that it is on file for later shipping features.
42. As a Vendor Owner, I want to edit my Store name, so that I can present it as I like.
43. As a Vendor Owner, I want to be unable to change my slug, status or commission, so that only the Platform controls those.
44. As a Vendor Owner, I want to be unable to see or edit any other Store's identity, so that my brand and data stay private.
45. As a Vendor Owner, I want a dashboard showing my Store's status, so that I know whether I am Draft, Active or Suspended.
46. As a Vendor Owner, I want a link from the dashboard to my public Store, so that I can see how customers see it.
47. As a Vendor Owner, I want to preview my Store while it is still Draft, so that I can check it before launch.
48. As a visitor, I want a Draft Store to look like it does not exist, so that unfinished shops are not exposed.
49. As a visitor, I want a Suspended Store to show a clear Arabic maintenance page, so that I understand it is temporarily unavailable.
50. As a visitor, I want the maintenance page to be returned with a "temporarily unavailable" signal, so that search engines do not treat the Store as gone.
51. As a visitor, I want a missing Store to show a friendly Arabic not-found page, so that a typo in the address is not a dead end.
52. As a visitor, I want an old Store address to take me to the new one, so that bookmarks and shared links keep working.
53. As a visitor, I want the Storefront to show the Store's logo, name, colours, favicon, footer text and contact links, so that it feels like one Vendor's own shop.
54. As a visitor, I want the Storefront right-to-left and comfortable on a phone, so that it is usable for the primary audience.
55. As a Platform Admin, I want a Suspended Store to be blocked from new cart additions, checkout and payout requests once those features exist, so that suspension actually stops new sales and money movement.
56. As a Platform Admin, I want in-flight Orders of a Suspended Store to keep progressing and funds to keep maturing, so that customers and Vendors with existing Orders are not harmed.
57. As a Platform Admin, I want payouts to resume on reactivation, so that suspension is reversible without manual repair.
58. As a developer, I want a single place that answers "is trading allowed for this Store", so that later phases (cart, checkout, payouts) check one rule instead of re-deriving it.
59. As a developer, I want a typed, cached settings service in the Core module, so that every module reads Platform rules the same way.
60. As a developer, I want the isolation helper to cover the new Store-owned data, so that the Phase 1 guarantee keeps holding.

## Implementation Decisions

- **Modules touched.** Core (Platform Settings and the colour-contrast helper), Tenancy (Store identity, status flow, slug redirects, invitations, onboarding actions, public-visibility middleware, Admin Panel Store resource extensions), Auth (invitation acceptance depends on the user and Vendor Owner role), Backoffice (Vendor Panel pages and the Admin Panel settings page), Storefront (identity-aware shell, maintenance and not-found pages). Dependency direction stays one-way; Tenancy must not reach upward into Auth, so invitation acceptance sits in Auth or goes through a Tenancy contract that Auth fulfils.
- **Platform Settings.** Key/value store with JSON values, read through a typed service in Core with caching and cache invalidation on write. Defaults are seeded for the eight keys named in the phase plan; money-like values are stored in piasters, percentages as plain numbers. The service exposes one typed accessor per setting rather than a generic string lookup.
- **Store identity data.** Add to the Store: primary colour, secondary colour, favicon, social links (JSON), footer text and shipping origin. The contact fields and logo already exist. Colours are validated as hex. Logo and favicon are stored under the Store's own file folder, with public visibility because they are shown on the Storefront.
- **Onboarding action.** One transactional action creates the Store (Draft), the Vendor Owner user bound to that Store, and an invitation. A second action re-sends an invitation and invalidates the previous one. Both are shared by the Admin Panel; the Panel only calls them.
- **Invitation.** A signed link valid 72 hours, single use, allowing the invited owner to set a password. After acceptance the owner is signed in and redirected to the Vendor Panel. Expired, used or tampered links show an Arabic explanation. The invitation email is Arabic and its text lives in the language files.
- **Store status flow.** Draft → Active, Active → Suspended, Suspended → Active. No other transition is allowed; Active → Draft is not allowed. The transition rules live in one place and are enforced by the actions, not by the UI alone.
- **Public visibility rule.** A single middleware governs the Storefront: Draft returns not-found to guests while the Store's own Vendor Owner (and Platform Admin) may preview; Suspended returns a maintenance page with HTTP 503 for the public. A single "can this Store trade" query on the Store (true only when Active) is the rule later phases use to refuse cart additions, checkout and payout requests. Suspension never touches in-flight Orders or maturing funds.
- **Slug change.** Admin-only action. Records the old slug in a redirect table and keeps it resolving with a permanent redirect. The new slug must pass the existing slug rules and must not collide with any current slug, Reserved Slug or recorded old slug. Chains collapse so an old address redirects straight to the current one.
- **Contrast helper.** A pure helper in Core computes the WCAG contrast ratio between two colours and returns black or white text for a given background. It drives both the Vendor Panel warning and the Storefront text colour. The warning is advisory; it does not block saving.
- **Storefront shell.** Injects the Store's colours and the derived text colour as the CSS variables defined in Phase 1, plus logo, favicon, footer text and contact links. Maintenance and not-found pages are right-to-left, Arabic and mobile first. Colour selection stays limited to the two colours; Theme selection is out of scope.
- **Admin Panel.** The Store resource grows from read-only to full create/edit with a status badge, a status filter and the actions Activate, Suspend, Resend invitation and Change slug (with redirect). The owner is shown through a relation manager. A Platform Settings page is visible to Platform Admins only.
- **Vendor Panel.** An accept-invitation page; a "Store identity" settings page limited to the owner's own Store, with no control over slug, status or commission; a dashboard placeholder showing status and a link to the public Store. The Vendor Panel's tenant scope remains the second isolation layer.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies (ADR 0001). Pest wording in the plan is superseded by PHPUnit for this repo, as in Phase 1.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: given this request, panel interaction or actor, what response, email, stored data or visible page results. Do not assert internal class structure, cache keys or which helper was called.
- **Seam 1: the HTTP and panel boundary (primary).** Feature tests through the Storefront routes, the Admin Panel and the Vendor Panel, including the invitation link. Cases:
  - creating a Store with owner produces a Draft Store, an owner bound to it, and an Arabic invitation email; a failure rolls everything back;
  - an invitation works once, within 72 hours, and not after expiry, reuse, tampering or being superseded by a resend; accepting signs the owner into the Vendor Panel;
  - slug validation (including live validation) and slug change with a permanent redirect, rejecting invalid, reserved, taken and previously used slugs;
  - Draft is not-found to guests but previewable by its own owner; Suspended returns 503 with the maintenance page; Active serves normally;
  - only the allowed status transitions succeed;
  - a Vendor Owner can edit their own identity but not slug, status or commission, and cannot reach another Store's identity;
  - the Storefront reflects the Store's colours, logo, favicon, footer text and contact links;
  - Platform Settings are Admin-only, validated, seeded with defaults, and a saved change is read back immediately.
- **Seam 2: pure units.** The contrast helper and the Settings service's typed accessors are pure enough to test directly with value-in/value-out cases (known WCAG pairs, black/white choice, default and overridden settings). This is the only place unit-level tests are warranted.
- **Isolation.** Reuse the Phase 1 isolation helper for any new Store-owned data (slug redirects, invitations, identity uploads) and add the cases: Store A's owner cannot read or change Store B's identity or files; an invitation for Store A cannot create access to Store B.
- **Framework and gate.** PHPUnit classes, no new test dependency. Formatting and the tests must pass; static analysis remains gated on the Larastan approval raised in Phase 1.
- **Prior art.** Phase 1 tests for panel access, slug rules, the customer guard and the isolation helper are the templates.

## Out of Scope

- Products, categories, Theme selection and Theme Settings beyond the two colours (Phase 3 and 4).
- Customers, customer screens and the customer authentication flow (Phase 5).
- Carts, checkout, Orders and payments; this phase only defines the trading-allowed rule they will consume (Phases 6 and 7).
- Payout requests and the ledger (Phases 8 and 10); this phase only guarantees suspension will not break them.
- Staff roles under a Vendor Owner (V2) and self-service Vendor sign-up (Vendors are invited only).
- Custom domains and subdomains.
- Shipping-origin behaviour; it is stored as free text only.
- Two-factor enforcement, audit logging and security headers (Phase 12).

## Further Notes

- **Tracker.** `docs/agents/issue-tracker.md` does not exist and this repo has no git remote; the project instructions point to a GitHub repo for a different product (THOB). Following the Phase 1 precedent, this spec lives in `docs/specs/` and its tickets go to `.scratch/phase-2-stores-identity/issues/` with the `ready-for-agent` label. Run `/setup-matt-pocock-skills` if a real tracker should be wired.
- **Seams to confirm.** The spec proposes one primary seam (HTTP and panel boundary) plus a small pure-unit seam for the contrast helper and Settings service, and reuses the existing isolation helper. Say so if you want fewer or different seams.
- **Suggested ticket order.** Platform Settings; contrast helper; Store identity columns and Vendor identity page; status flow plus visibility middleware and maintenance page; slug change and redirects; onboarding action, invitation and email; accept-invitation page; Admin Store resource actions; Admin settings page; Storefront identity shell; Vendor dashboard; phase gate.
- **Open assumptions.** Invitation resend before acceptance only; Draft preview is granted to the Store's own Vendor Owner and to Platform Admins; the contrast warning is advisory, not blocking; Active → Draft is deliberately not allowed.
