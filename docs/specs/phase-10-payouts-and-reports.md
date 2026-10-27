# Phase 10: Payouts and Reports

**Status:** ready-for-agent
**Prerequisite:** Phases 1–9 closed (Phase 8 provides Available balances and Reconciliation; Phase 9 provides delivery and refunds). Source: `docs/project-phases-ai-prompts.md`, Phase 10, and `docs/REVIEW-ROUND-1.md` R2-10 and R3-5. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

Vendors accumulate Available money but cannot ask for it, so the Platform's debt to them only grows. A Platform Admin has no queue of requests, no safe way to record that a transfer was made, and no proof the balance moved correctly afterwards. Neither side has dashboards or exports: a Vendor cannot see how their Store is selling or hand a statement to their accountant, and the Platform cannot see revenue, Commission or what it still owes without reading raw Ledger Entries.

## Solution

A Vendor Owner saves their payout details (kept encrypted), then requests a Payout for any amount between the Platform minimum and their Available balance. The request moves that money from Available to Payout Pending and appears in the Platform Admin's queue. The Platform Admin transfers the money outside the system, then records the transfer reference (and optionally a proof image), which moves the money to Paid. A request can be rejected with a reason, or cancelled by the Vendor, which returns the money to Available. Both panels gain dashboards, and the Vendor's statement and the Platform's revenue report export to CSV.

## User Stories

1. As a Vendor Owner, I want to save how I want to be paid (Vodafone Cash, InstaPay or bank) with my number, handle or IBAN, so that the Platform knows where to send my money.
2. As a Vendor Owner, I want my payout details stored encrypted, so that a database leak does not expose my account.
3. As a Vendor Owner, I want to edit my payout details at any time, so that I can change account.
4. As a Vendor Owner, I want a request to keep the details as they were when I made it, so that a later edit cannot redirect a transfer already in progress.
5. As a Vendor Owner, I want to request a Payout of any amount between the minimum and my Available balance, so that I choose how much to take.
6. As a Vendor Owner, I want to see my Available balance and the minimum when I request, so that I enter a valid amount.
7. As a Vendor Owner, I want a request above my Available balance refused, so that I cannot take money I have not earned.
8. As a Vendor Owner, I want a request below the minimum refused with a clear message, so that I know the threshold.
9. As a Vendor Owner, I want only one open request at a time, so that my balance is not split confusingly.
10. As a Vendor Owner, I want to be unable to request without payout details saved, so that the Platform always knows where to pay.
11. As a Vendor Owner, I want to cancel my open request, so that I can change my mind before it is paid.
12. As a Vendor Owner, I want a cancelled or rejected request to return its amount to Available at once, so that I never lose track of money.
13. As a Vendor Owner, I want a history of my Payouts with status, amount, dates, reference and any rejection reason, so that I can follow what happened.
14. As a Vendor Owner, I want my balances (Pending, Available, Payout Pending, Paid) to match my Payouts, so that the numbers agree everywhere.
15. As a Vendor Owner, I want requests refused while my Store is suspended and allowed again on reactivation, so that the Platform can pause an account safely.
16. As a Vendor Owner, I want my funds to keep maturing while suspended, so that I lose nothing from a pause.
17. As a Vendor Owner, I want to be notified when my Payout is paid or rejected, so that I do not need to check.
18. As a Vendor Owner, I want to see only my own Store's Payouts and details, so that other Vendors' finances stay private.
19. As a Vendor Owner, I want a dashboard chart of sales over the last 30 days, so that I see how my Store is doing.
20. As a Vendor Owner, I want to see how many Orders await shipping, so that I know my workload.
21. As a Vendor Owner, I want my top products listed, so that I know what sells.
22. As a Vendor Owner, I want to export my statement for a date range to CSV, so that I can reconcile my own books.
23. As a Vendor Owner, I want the exported statement to carry date, Order, description, amount and running balance, so that it matches the screen.
24. As a Platform Admin, I want a queue of requested Payouts with a badge count, so that none is forgotten.
25. As a Platform Admin, I want to open a request and see the Vendor's payout destination, so that I can make the transfer.
26. As a Platform Admin, I want destination details decrypted for Admins only, so that the secret is as narrow as possible.
27. As a Platform Admin, I want to mark a request paid by entering the transfer reference, so that the transfer is on record.
28. As a Platform Admin, I want to attach a proof image of the transfer, so that disputes can be settled.
29. As a Platform Admin, I want marking paid to be refused without a transfer reference, so that every payout is traceable.
30. As a Platform Admin, I want a request marked paid to move the amount to Paid in the books, so that balances and cash stay correct.
31. As a Platform Admin, I want to reject a request with a reason, so that the Vendor knows why.
32. As a Platform Admin, I want a rejection to return the money to Available, so that the Vendor can request again.
33. As a Platform Admin, I want a request to be actionable only while it is requested, so that a paid or cancelled one cannot be changed.
34. As a Platform Admin, I want repeating a click or a second tab to change nothing, so that money cannot move twice.
35. As a Platform Admin, I want every action on a Payout to record who did it and when, so that it is audited.
36. As a Platform Admin, I want two requests for the same Store at once to be impossible to combine into an overdraw, so that the balance can never go negative.
37. As a Platform Admin, I want Reconciliation to stay at zero through request, paid, reject and cancel, so that the books are trusted.
38. As a Platform Admin, I want a dashboard with GMV for 30 days, Commission revenue, Receipts awaiting review and Payouts awaiting, so that I see the business at a glance.
39. As a Platform Admin, I want a chart of Orders by status, so that I see where Orders sit.
40. As a Platform Admin, I want my top Stores listed, so that I know who drives sales.
41. As a Platform Admin, I want a reports page showing revenue by period and by Store, so that I can analyse Commission.
42. As a Platform Admin, I want to export the revenue report for a date range to CSV, per Store, so that I can use it elsewhere.
43. As a Platform Admin, I want figures to be correct to the piaster, so that reports agree with the Ledger.
44. As a Platform Admin, I want refunded and cancelled Orders handled correctly in reports, so that revenue is not overstated.
45. As a Platform Admin, I want to set the minimum payout in Platform Settings, so that I can tune it without a release.
46. As a developer, I want dashboard queries cached briefly with Store-prefixed keys where Store-scoped, so that dashboards are fast and tenants never share cache.
47. As a developer, I want payout postings to use the existing Poster, so that every group balances and repeats safely.
48. As a developer, I want the Payout entries to follow the Phase 8 account model, so that balances need no new accounting.
49. As a developer, I want the isolation helper to cover Payouts and payout details, so that tenant isolation keeps holding.

## Implementation Decisions

- **Placement.** Payouts and the Ledger entries they create belong in the Ledger module. Reports are read-only queries over Sales and Ledger data, in Ledger for money figures and Backoffice (Admin) and Vendor panel pages for display. Sales and Payments do not depend on Ledger.
- **Payout.** Store-owned. Fields: Store, amount (piasters), status (requested, paid, rejected, cancelled), transfer method (Vodafone Cash, InstaPay, bank), destination snapshot (encrypted), transfer reference, optional proof file path, requested time, processed-by and processed time, rejection reason, and who cancelled and when. Status moves only requested → paid, rejected or cancelled; nothing else.
- **Payout details.** Held on the Store: method plus number, handle or IBAN, stored encrypted at rest, editable by the Vendor Owner. A request copies them as an encrypted snapshot. Admins see the decrypted snapshot on the Payout view; Vendors see their own saved details but never another Store's.
- **Request rules.** Rejected while the Store is suspended. Amount must be at least the minimum (setting `min_payout_amount`, default 500 EGP, already present) and at most Available. One open (requested) Payout per Store. A Store without saved payout details cannot request. Partial payouts are allowed. The Available balance is read and the request posted under a per-Store lock in one transaction so two concurrent requests cannot exceed it.
- **Ledger postings (via the Poster, one balanced group per action, keyed by Payout and action).**
  - Request: debit Vendor payable available, credit Vendor payout pending, for the amount.
  - Paid: debit Vendor payout pending, credit Platform cash, for the amount.
  - Cancel or reject: the reverse of the request, referencing the original entries.
  Each posting carries the Payout reference. Repeating any action inserts nothing.
- **Balances.** The existing balance service gains the real Paid figure, derived from the paid postings; Payout Pending is the sum of open requests. Reconciliation is unchanged and must stay at zero after every combination of the four actions.
- **Actions.** Request and cancel by the Vendor Owner; mark paid and reject by a Platform Admin. Each is idempotent, audited (actor, time), re-reads the Payout under lock and refuses if its status is no longer requested. Marking paid requires a transfer reference; a proof image is optional (image or PDF, stored privately, viewable by Admins only).
- **Notifications.** The Vendor Owner is notified in-app when a Payout is paid or rejected, using the same notification approach as earlier phases. Admins see the queue badge.
- **Suspended Stores.** Per R3-5: no new requests while suspended; an already-open request may still be paid, rejected or cancelled; funds keep maturing; requests resume on reactivation.
- **Reporting services.** Sales by day, Orders by status, top products, GMV, Commission revenue by day and by Store, and Payouts pending. Definitions: GMV is paid Order totals in the period excluding cancelled; refunded amounts are subtracted when refunded; Commission revenue is read from the books, not recomputed. Store-scoped results are cached briefly under keys prefixed with the Store; Platform-wide results use a platform prefix. Any change to the figures is visible within the cache lifetime.
- **CSV exports.** Store statement for a date range (the Vendor's own Store only) and Platform revenue for a date range, per Store (Admins only). Money is exported as decimal EGP with two places, dates in ISO form, text safe against spreadsheet formula injection, UTF-8 with a byte-order mark so Arabic opens correctly. Large ranges stream rather than load into memory.
- **Admin Panel.** A Payout resource with a navigation badge for requested Payouts, a view page showing the decrypted destination, and Mark paid (reference required, proof optional) and Reject (reason required) actions. Dashboard widgets: GMV for 30 days, Commission revenue, Receipts awaiting review, Payouts awaiting, Orders by status chart, top Stores. A Reports page: revenue by period and by Store with export.
- **Vendor Panel.** A Payouts page with the details form, request action (shows Available balance and minimum, validates) and history with statuses and cancel on the open request. Dashboard widgets: sales for the last 30 days chart, Orders to ship, top products, balances. The Statement page gains a CSV export.
- **Platform Settings.** The minimum payout setting already exists; its validation and Admin form stay as they are.
- **Storefront.** No change.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies; PHPUnit replaces Pest wording.

## Testing Decisions

- **What makes a good test.** Assert outcomes only: Payout statuses, balances, books, notifications, what each actor sees, and the contents of exports. Do not assert class structure or query shape.
- **Seam: the panel, command and download boundary (the only seam).** Tests drive the Vendor and Admin Filament panels (requests, actions, widgets, pages, exports), observe the Ledger through balances and Reconciliation, and read exported CSV from the response. No pure-unit seam is proposed; report arithmetic is checked through exports and widget state against seeded Orders and Ledger data.
- **Cases:**
  - request above Available blocked; below minimum blocked; exactly the minimum and exactly Available accepted;
  - second open request blocked; request without saved details blocked; request while suspended blocked and allowed after reactivation;
  - concurrent requests cannot exceed Available;
  - postings for request, paid, reject and cancel each balance and return balances to the expected values; Reconciliation is zero after a full cycle and after a mixed run;
  - paid needs a transfer reference; proof optional; a paid, rejected or cancelled Payout cannot be acted on again; repeating an action posts nothing twice;
  - rejection needs a reason and restores Available; Vendor cancel restores Available;
  - encryption at rest (the stored value is not the plain text); only Admins see destination; a Vendor never sees another Store's Payouts or details; the snapshot survives a later edit of the details;
  - Paid balance equals the sum of paid Payouts; Payout Pending equals the open request;
  - notifications on paid and rejected;
  - an open request can still be settled while the Store is suspended;
  - dashboard figures match seeded data, including refunded and cancelled Orders; Store-scoped caches do not leak between Stores;
  - CSV contents: columns, running balance, date-range filtering, piaster-exact amounts, Arabic text intact, formula-injection safe; a Vendor cannot export another Store's statement; only Admins export Platform revenue;
  - isolation for Payouts and payout details.
- **Prior art.** Phase 8 Ledger and Reconciliation tests and the Phase 1 isolation helper; Phase 7 and 9 panel and refund tests for actions, notifications and concurrency.
- **Gate.** PHPUnit and formatting pass; no front-end change expected.

## Out of Scope

- Automatic transfers and any payment-gateway payout integration.
- Accounting integrations, tax, invoices and statements for Customers.
- Multi-currency.
- Scheduled or recurring payouts, and batch payouts across Stores.
- Payout fees or deductions.
- The Satisfaction Survey and consistent notification templates (Phase 11).
- Resolving Phase 9 review findings, except where noted below.

## Further Notes

- **Tracker.** The repo has no remote tracker configured, so, as in earlier phases, this spec lives in `docs/specs/` and tickets go to `.scratch/phase-10-payouts-and-reports/issues/` as `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real one.
- **Seams to confirm.** A single seam (panels, commands, downloads). Say so if you want a unit seam for the report arithmetic.
- **Suggested ticket order.** Payout model and payout details with encryption; balance service Paid figure; request and cancel actions with locking; mark paid and reject actions with notifications; Admin Payout resource and badge; Vendor Payouts page; reporting services; Admin widgets and Reports page; Vendor widgets; CSV exports; isolation and phase gate.
- **Open assumptions.** GMV counts paid Orders excluding cancelled and nets refunds; the Vendor's open request can be cancelled only while requested; proof files are private and Admin-only; CSV is UTF-8 with a byte-order mark; payout details are one set per Store (no multiple accounts).
- **Carry-over from the Phase 9 review.** Forcing an Order from Delivered to Completed leaves its funds Pending while it reads Completed; resolve it before relying on Available balances for payouts. The concurrent same-reference refund error and moving refundable-status rules onto the Order status are also open.
- **Dependency.** Phase 9 is complete and committed (`d772ee7`), so this phase can start.
