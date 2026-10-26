# Phase 8: Ledger and Commission

**Status:** ready-for-agent
**Prerequisite:** Phases 1–7 closed (Phase 7 emits `PaymentApproved`). Source: `docs/project-phases-ai-prompts.md`, Phase 8. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

Once an Order is paid, the Platform holds the Customer's money but has no record of whom it owes. A Vendor cannot see what they have earned or when it can be paid out, the Platform Admin cannot tell how much cash is really Commission and how much belongs to Vendors, and nothing can detect a mistake. Payouts and refunds, which come next, have nothing reliable to build on.

## Solution

Every money movement becomes an immutable Ledger Entry. When a payment is approved, the Platform records the cash received, what the Vendor is owed (held as Pending) and the Commission it keeps, always in balanced sets. After the Return Window has passed following delivery, the Vendor's money moves from Pending to Available and the Order completes. Refunds will later reverse entries rather than edit them. A daily Reconciliation proves the books balance and alerts Platform Admins if not. Platform Admins see every entry and every Vendor's balances; each Vendor sees only their own balances and statement.

## User Stories

1. As a Platform Admin, I want every approved payment to create ledger entries automatically, so that no money movement depends on someone remembering.
2. As a Platform Admin, I want an Order of 1,200 EGP products and 60 EGP shipping at 10% Commission to record cash 1,260, Vendor owed 1,140 and Commission 120, so that the arithmetic is exactly right.
3. As a Platform Admin, I want each set of entries for an event to balance (debits equal credits), so that the books cannot drift.
4. As a Platform Admin, I want Commission taken from the rate frozen on each Order Item, so that later rate changes never alter past Orders.
5. As a Platform Admin, I want Commission rounded per item to whole piasters with the Vendor receiving the remainder, so that rounding never creates or loses money.
6. As a Platform Admin, I want shipping fees to go wholly to the Vendor with no Commission, so that delivery costs are not taxed.
7. As a Platform Admin, I want a repeated payment-approved event to post nothing the second time, so that retries cannot double-count.
8. As a Platform Admin, I want entries to be impossible to change or delete, through the application or directly in the database where supported, so that history can be trusted.
9. As a Platform Admin, I want corrections to be made by new opposite entries that point to the original, so that the audit trail stays complete.
10. As a Platform Admin, I want partial reversals to take proportional Commission, so that a part-refund is fair to both sides.
11. As a Platform Admin, I want Vendor money to stay Pending until the Return Window has ended, so that refunds can be paid from it.
12. As a Platform Admin, I want delivered Orders past the Return Window, not refunded, to move to Available hourly, so that Vendors are paid on time without manual work.
13. As a Platform Admin, I want that move to be repeatable without effect, so that a rerun cannot release money twice.
14. As a Platform Admin, I want an Order to become Completed when its money is released, through the Order state machine, so that status and money agree.
15. As a Platform Admin, I want to browse all Ledger Entries, filtered by Store, Order, account and date, so that I can investigate any amount.
16. As a Platform Admin, I want the list to offer no create, edit or delete, so that nobody is tempted.
17. As a Platform Admin, I want a Vendor balances page showing Pending, Available, Payout Pending and Paid per Store, so that I know what we owe.
18. As a Platform Admin, I want to open a Store's balance and see its entries, so that I can explain any figure.
19. As a Platform Admin, I want platform totals (cash, Payables, Commission revenue), so that I see the whole position.
20. As a Platform Admin, I want a daily Reconciliation of total debits against credits, cash against Payables plus Commission, and each Order against its entries, so that mistakes surface within a day.
21. As a Platform Admin, I want each run stored with its result and details, so that I can see history.
22. As a Platform Admin, I want to be notified when a run finds any difference, so that I act at once.
23. As a Platform Admin, I want a widget with the last run's status and any mismatch, so that I see health at a glance.
24. As a Platform Admin, I want to run Reconciliation on demand, so that I can check after a fix.
25. As a Platform Admin, I want to set a per-Store Commission Rate override between 0% and 50%, so that I can agree special terms.
26. As a Platform Admin, I want a rate outside that range refused, so that a typo cannot give away revenue.
27. As a Platform Admin, I want a changed rate to apply only to Orders placed afterwards, so that past Orders stay as sold.
28. As a Vendor Owner, I want my Pending, Available and Paid balances on my dashboard, so that I know where I stand.
29. As a Vendor Owner, I want a statement of my Store's entries with date, Order, an Arabic description, amount and running balance, so that I can reconcile my own books.
30. As a Vendor Owner, I want to filter my statement by date range, so that I can review a period.
31. As a Vendor Owner, I want to see my net amount on each Order, so that I know what I will earn.
32. As a Vendor Owner, I want to see only my own Store's entries and balances, so that other Vendors' finances stay private.
33. As a Vendor Owner, I want never to see the Customer's payment details or Receipt, so that Customer privacy holds.
34. As a Customer, I want nothing about Commission or Vendor earnings to appear in any page I see, so that internal figures stay internal.
35. As a developer, I want a service that reverses entries fully or partially, so that Phase 9 refunds need no new accounting.
36. As a developer, I want a balance service computed from entries and cached with invalidation, so that dashboards are fast and never stale.
37. As a developer, I want the isolation helper to cover Store-owned ledger rows, so that tenant isolation keeps holding.
38. As a developer, I want random Orders to keep Reconciliation at zero, so that unusual amounts cannot break the books.

## Implementation Decisions

- **New Ledger module.** Depends on Sales, Payments (event only), Tenancy and Core. Sales and Payments do not depend on Ledger; Ledger listens to `PaymentApproved`.
- **Ledger Entries.** Append-only. Fields: Store (empty for Platform-only accounts), Order and Payout references (optional), account, direction (debit or credit), amount in whole piasters, optional pointer to the entry it reverses, an optional "available at" time, a unique idempotency key, free-form details and a created time. Accounts: Platform cash, Vendor payable pending, Vendor payable available, Vendor payout pending, Platform Commission revenue.
- **Immutability.** The model throws on update and delete. Where the database supports it, triggers also reject update and delete; the SQLite test database keeps the model-level guard only. Bulk query deletes and updates are blocked through the model as well.
- **Posting on payment.** One posting group per approved Payment, keyed by the Payment: debit Platform cash for the Order total; credit Vendor payable pending for (product lines minus Commission) plus shipping; credit Commission revenue for the Commission total. Commission is the sum of the frozen per-item Commission Amounts. A group is refused unless debits equal credits. Posting is in one transaction; a second event for the same Payment inserts nothing.
- **Releasing funds.** An hourly job finds Orders that are delivered, not refunded, and delivered at least the Return Window ago (read from `delivered_at` and the platform setting), whose funds are still Pending. It moves the Vendor's net from Pending to Available (idempotent per Order) and completes the Order through the state machine, in one transaction per Order. A failure on one Order does not stop the others.
- **Reversals.** A service creates opposite entries referencing the originals, for a full or partial amount, taking Commission proportionally and rounding so that totals still balance. It draws on Pending or Available depending on where the Order's funds currently sit. Refund screens are Phase 9.
- **Balances.** A service returns, per Store, Pending, Available, Payout Pending and Paid, computed from entries and cached per Store with invalidation on every posting; it also returns Platform totals. Paid is defined by Payout entries that arrive in Phase 10 and is zero until then.
- **Reconciliation.** A daily command and an on-demand Admin action check: total debits equal total credits; Platform cash equals Payables (all three states) plus Commission revenue; each Order's entries agree with its totals and status. Each run is stored with outcome and details; any mismatch notifies all Platform Admins and shows in the widget.
- **Commission settings.** The default rate already exists in Platform Settings. The Store's override is edited on the Store form in the Admin Panel, accepting 0–50 and refusing anything else. A changed rate affects only later Orders, because Order Items freeze their rate.
- **Admin Panel.** A read-only Ledger Entry resource with Store, Order, account and date filters; a Vendor balances page with drill-down to a Store's entries; a Reconciliation widget with a Run now action.
- **Vendor Panel.** Dashboard widgets for Pending, Available and Paid; a Statement page of the Store's own entries with an Arabic description, signed amount, running balance and a date-range filter; the Vendor Order view shows the net amount for that Order and nothing about the Customer's payment.
- **Storefront.** No change. A test confirms Commission and ledger figures do not appear in any Customer-facing page data.
- **Scheduling.** Releasing funds hourly; Reconciliation daily. Both safe to run twice.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies; PHPUnit replaces Pest wording.

## Testing Decisions

- **What makes a good test.** Assert outcomes only: entries recorded, balances shown, statuses, notifications and what each actor can see. Do not assert class structure or query shape.
- **Seam: the event, command and panel boundary (the only seam).** Tests fire `PaymentApproved` (or approve a Payment through the Phase 7 action), run the scheduled commands, and drive the Admin and Vendor panels and Storefront responses. No pure-unit seam is proposed; the rounding rules are exercised through postings.
- **Cases:**
  - the worked example to the piaster (1,260 / 1,140 / 120);
  - rounding across several items and rates; every group balances;
  - repeated event posts once; a concurrent duplicate posts once;
  - update and delete refused, including bulk;
  - full and partial reversal, proportional Commission, still balanced;
  - release timing: not before the Return Window, once after, not for refunded Orders, repeatable, Order becomes Completed, one failing Order does not block others;
  - balances equal entries and refresh after each posting;
  - Reconciliation passes on valid data, fails and notifies on a planted mismatch; on-demand run; stored result;
  - random Orders keep Reconciliation at zero;
  - Admin list is read-only and filterable; Vendor sees only own entries, balances and statement, with correct running balance and date filter; Vendor Order view shows net but no payment details;
  - override rate accepts 0–50, refuses outside, applies only to later Orders;
  - no Commission figure in any Customer-facing page data;
  - isolation for Store-owned ledger rows.
- **Prior art.** Phase 6 and 7 tests for Orders, expiry and Admin and Vendor panels; the Phase 1 isolation helper.
- **Gate.** PHPUnit and formatting pass; no front-end change expected.

## Out of Scope

- Payouts and the Paid state's entries (Phase 10).
- Refund screens and delivery tracking (Phase 9), beyond the reversal service.
- Tax, invoices and accounting exports.
- Multi-currency.
- Customer-facing statements.

## Further Notes

- **Tracker.** The repo has no remote tracker configured, so, as in earlier phases, this spec lives in `docs/specs/` and tickets go to `.scratch/phase-8-ledger-and-commission/issues/` as `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real one.
- **Seams to confirm.** A single seam (events, commands and panels). Say so if you want a unit seam for the rounding.
- **Suggested ticket order.** Ledger Entry model with immutability; posting on payment; Balance service; release job and completion; reversal service; Reconciliation; Admin resources and widget; Store override; Vendor widgets, statement and Order net; Storefront leak test; phase gate.
- **Open assumptions.** Commission rounds per item with the Vendor taking the remainder; shipping carries no Commission; reversals draw on Pending until released, then Available; Payout Pending and Paid stay zero until Phase 10.
- **Dependency.** Phase 7 is complete and committed (`PaymentApproved` is emitted when a Payment is approved), so this phase can start.
