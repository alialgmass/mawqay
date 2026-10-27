# Phase 9: Shipping, Delivery and Refunds

**Status:** ready-for-agent
**Prerequisite:** Phases 1–8 closed (Phase 8 provides the reversal service and the funds-release job that reads `delivered_at`). Source: `docs/project-phases-ai-prompts.md`, Phase 9. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

A paid Order goes nowhere. A Vendor has no way to say they are preparing it, no place to record the courier and tracking number, and no way to mark it delivered, so the Return Window never starts and the Vendor's money stays Pending forever. A Customer cannot see where their Order is or tell the Store they received it. When something goes wrong (wrong size, defective, never arrived) the Platform Admin has no way to hand money back, and the books have no refund to reverse against.

## Solution

Vendors move their own paid Orders through processing, shipped (with courier name and tracking number) and delivered from the Vendor Panel. Customers see a timeline of their Order with the tracking details, and can confirm receipt themselves. Whichever of delivery or confirmation comes first starts the Return Window and is never overwritten. A Platform Admin can refund an Order in full or in part from the Admin Panel with a fixed reason, optionally returning the goods to stock; the refund reverses the Ledger proportionally, tells the Customer and the Vendor, and is recorded. Admin overrides of status are possible only with a reason and leave an audit entry.

## User Stories

1. As a Vendor Owner, I want to start processing a paid Order, so that the Customer knows I have begun preparing it.
2. As a Vendor Owner, I want to mark an Order shipped with a courier name and tracking number, so that the Customer can follow it.
3. As a Vendor Owner, I want the courier name to be required and the tracking number, tracking link and note to be optional, so that couriers without tracking still work.
4. As a Vendor Owner, I want to mark a shipped Order delivered, so that the Return Window starts and my money can be released.
5. As a Vendor Owner, I want to act only on my own Store's Orders, so that no one can touch another Vendor's Orders and I cannot touch theirs.
6. As a Vendor Owner, I want each action offered only when the Order is in the right prior status, so that I cannot skip steps or go backwards.
7. As a Vendor Owner, I want status tabs with counts ("to ship", "shipped"), so that I see my workload at a glance.
8. As a Vendor Owner, I want to print an Arabic right-to-left packing slip for an Order, so that I can pack and label it.
9. As a Vendor Owner, I want the packing slip to show the shipping address and items but never the Customer's payment details, so that Customer privacy holds.
10. As a Vendor Owner, I want a repeated click or a second tab not to apply an action twice, so that logs stay clean.
11. As a Vendor Owner, I want to be told clearly when an Order moved since I looked at it, so that I do not act on stale information.
12. As a Vendor Owner, I want to see the shipment details I entered on the Order, so that I can answer the Customer's questions.
13. As a Vendor Owner, I want to be notified when an Order I fulfilled is refunded, so that I understand why my balance changed.
14. As a Customer, I want a timeline of my Order's statuses with dates, so that I know where it is.
15. As a Customer, I want to see the courier name and tracking number, so that I can follow it with the courier.
16. As a Customer, I want the tracking number to be a link when the Vendor gave one, so that I can open it in one tap.
17. As a Customer, I want an "I received my order" button once it is shipped, so that I can close the loop myself.
18. As a Customer, I want my confirmation to start the Return Window if the Vendor has not yet marked it delivered, so that neither side waits on the other.
19. As a Customer, I want to confirm receipt only for my own Orders, so that no one can confirm for me.
20. As a Customer, I want the Store's contact block on the Order page, so that I know whom to reach for problems or returns.
21. As a Customer, I want to be notified when my Order is shipped, delivered and refunded, so that I do not have to check.
22. As a Customer, I want a refund notification that states the amount, so that I know what to expect.
23. As a Customer, I want to see Order timelines in Arabic right-to-left on mobile, so that the page works for me.
24. As a Customer, I want never to see Commission, Ledger figures or the Vendor's internal notes, so that internal figures stay internal.
25. As a Platform Admin, I want to refund an Order in full, so that a Customer who should not have paid gets all their money back.
26. As a Platform Admin, I want to refund part of an Order's value, so that I can compensate without cancelling everything.
27. As a Platform Admin, I want to choose a reason from a fixed list (wrong size, changed mind, defective, not delivered, other) with an optional note, so that refunds are comparable.
28. As a Platform Admin, I want a refund allowed on paid, processing, shipped and delivered Orders, so that I can help at any point before completion.
29. As a Platform Admin, I want a refund on a Completed Order to need an explicit override with a written reason, so that closed money is not reopened casually.
30. As a Platform Admin, I want a refund larger than what remains refundable to be refused, so that I can never refund more than was paid.
31. As a Platform Admin, I want a full refund to move the Order to Refunded, so that its status matches the money.
32. As a Platform Admin, I want a partial refund to leave the Order's status alone but record the refund, so that fulfilment continues.
33. As a Platform Admin, I want several partial refunds on one Order to add up and stop at the amount paid, so that the total cannot exceed it.
34. As a Platform Admin, I want the Vendor's loss from a refund to be proportional to Commission, so that the Platform returns its share of Commission too.
35. As a Platform Admin, I want the refund to reverse Ledger entries through the existing reversal service, so that balances and Reconciliation stay correct.
36. As a Platform Admin, I want a refund on a refunded-from-Pending or already-Available Order to draw on the right Vendor balance, so that accounting matches where the money sits.
37. As a Platform Admin, I want an optional "return goods to stock" toggle, so that sellable items go back on the shelf.
38. As a Platform Admin, I want the restock to return only the quantities refunded, so that a partial refund does not inflate stock.
39. As a Platform Admin, I want repeating a refund submission not to refund twice, so that a double click is harmless.
40. As a Platform Admin, I want a refund to be recorded with amount, reason, restock choice and who did it, so that it can be audited.
41. As a Platform Admin, I want to force a status change with a mandatory reason, so that I can repair a stuck Order.
42. As a Platform Admin, I want every forced change to be audited, so that overrides are accountable.
43. As a Platform Admin, I want a forced change never to bypass money rules, so that it cannot create or lose cash.
44. As a Platform Admin, I want to see the shipments of any Order, so that I can answer a Customer's complaint.
45. As a Platform Admin, I want a refunded Order to stay out of the funds-release job, so that refunded money is never released.
46. As a developer, I want the Vendor's and Customer's actions to go through one place per transition, so that rules are written once.
47. As a developer, I want delivery to stamp `delivered_at` once and never overwrite it, so that the Return Window starts from the true first event.
48. As a developer, I want shipping behind a contract resolved from configuration, so that a courier gateway can be plugged in later.
49. As a developer, I want every status change logged with its actor, so that the timeline is trustworthy.
50. As a developer, I want the theme contract version bumped with the new Order page data, so that Themes know what they receive.
51. As a developer, I want the isolation helper to cover shipments and refunds, so that tenant isolation keeps holding.

## Implementation Decisions

- **Shipping module.** New, depending on Sales and Tenancy (and Core). Sales does not depend on Shipping; the Order state changes are made through Sales' state machine, while Shipping holds the shipment record and the actions that need it. Refunds belong with Sales and call Ledger through the Phase 8 reversal service via a contract/event, never a direct upward relation (Ledger remains downstream of Sales).
- **Shipment.** One per Order in the MVP (a later split shipment is out of scope). Store-owned (store scope applies). Fields: Order, courier name (free text, required at ship time), tracking number (optional), tracking link (optional, must be a valid web address when given), note (optional), shipped time, delivered time.
- **Shipping contract.** A `create` and a `track` operation, with the manual implementation as the only one registered. The implementation is resolved from configuration. `track` on the manual provider returns what the Vendor recorded.
- **Transitions.** Four actions, each through the state machine with `onlyFrom` guarding against stale views and each logging the actor:
  - *Start processing:* Paid → Processing. Vendor (own Store only).
  - *Mark shipped:* Processing → Shipped. Requires courier name. Creates the Shipment. Notifies the Customer.
  - *Mark delivered:* Shipped → Delivered. Sets `delivered_at` only if empty, dispatches an order-delivered event, notifies the Customer. Vendor (own Store only).
  - *Confirm receipt:* Shipped → Delivered. Customer, own Order only. Same effect as above.
  Whichever of mark delivered and confirm receipt occurs first stamps `delivered_at` and starts the Return Window; the loser of the race is refused as an invalid transition and no timestamp is overwritten. The state machine's existing row lock is the guard.
- **Completion.** Unchanged: the Phase 8 hourly job takes Delivered Orders whose `delivered_at` is at least the Return Window old and not refunded, releases funds and completes them. This phase adds no new completion path; it verifies the hand-off end to end.
- **Refund action (Admin only).** Inputs: Order, amount (full when omitted), reason from the fixed list, optional note, restock toggle, an idempotency reference. Allowed from Paid, Processing, Shipped and Delivered; from Completed only with an explicit override flag plus written reason, audited. Refused for Cancelled, Refunded and unpaid statuses. The amount may not exceed what remains refundable (paid total minus earlier refunds).
  - Money: calls the reversal service for the amount, keyed per Refund so a repeat is a no-op. Commission, Pending and Available are handled by that service.
  - Status: a refund that leaves nothing refundable moves the Order to Refunded through the state machine; a partial refund keeps the status and writes only the Refund record.
  - Restock: when toggled, returns the refunded quantities to stock. For a partial refund the admin picks the item quantities to restock; a full refund returns all. This needs a new stock operation for goods that already left the shelf (committed stock), since the existing operations only release reservations.
  - Notifies the Customer and the Vendor; dispatches an order-refunded event.
  - The whole action is one transaction (refund row, reversal, status, restock).
- **Refund record.** Order, amount, reason, note, restocked flag, actor, reversal reference, created time. Store-owned.
- **Funds interplay.** A refunded Order is excluded from release by the existing job. A partial refund on an Order that is still Pending reduces what is later released (the reversal already took it from Pending). One that arrives after release draws on Available.
- **Force status change (Admin).** Allows moving an Order to any status the machine permits plus a documented set of repairs; reason is mandatory; each use writes an audit entry (actor, from, to, reason). It must not bypass money: moving to Refunded this way is refused (use Refund), and moving into Paid is refused (use Payment Review).
- **Audit trail.** The codebase has no general audit log yet. This phase adds a minimal admin-override audit entry (actor, subject, action, reason, time) inside the module that owns the override, reusing the order status log where it already captures the change and adding a note. No new base folders.
- **Customer notifications.** Shipped, delivered and refunded, using the same notification approach as Payments. The Customer reads them in the language of the Store (Arabic default).
- **Storefront.** The Order page data gains: timeline (from the status log, Customer-safe labels only), shipment (courier, tracking number, tracking link when present), a flag for whether the Customer can confirm receipt, and the Store's contact block. A POST route lets the Customer confirm receipt for their own Order. The theme contract minor version is bumped and its doc updated; the default Theme renders the new parts. No Commission or Ledger figure appears.
- **Admin Panel.** On the Order resource: Refund action (amount, reason, note, restock with quantities, override for Completed), Force status change action (reason required), a Shipments relation manager. An audit entry for each override.
- **Vendor Panel.** On the Order resource: Start processing, Mark shipped (modal: courier name, tracking number, tracking link, note), Mark delivered; status tabs with counts; a packing slip action producing an Arabic right-to-left PDF. A PDF capability is not currently installed; the packing slip needs one added, which is a dependency change requiring approval before implementation.
- **Authorization.** Vendor actions through the Order policy limited to the Vendor's own Store; Customer confirmation limited to the Order's owner; refunds and force changes limited to Platform Admins.
- **Suspended Stores.** In-flight Orders continue to be fulfilled when a Store is suspended (per the glossary); new sales and payouts are the blocked actions. Fulfilment actions remain available.
- **Phase plan wins.** Where the PRD and the phase plan differ, the phase plan applies; PHPUnit replaces Pest wording.

## Testing Decisions

- **What makes a good test.** Assert outcomes through the boundary only: statuses, timeline, notifications, Ledger balances, stock levels and what each actor can see or do. Do not assert class structure or query shape.
- **Seam: the panel, storefront-route and scheduled-command boundary (the only seam).** Tests drive the Vendor and Admin Filament panels and Storefront responses, and run the existing funds-release command. Phase 8 reversal and balance behaviour is observed through Ledger balances rather than by calling the reversal service directly. No pure-unit seam is proposed.
- **Cases:**
  - full path Paid → Processing → Shipped → Delivered with the actor logged each time;
  - courier name required to ship; tracking number and link optional; invalid link refused;
  - each action refused from a wrong prior status, including a stale view (Order moved since the page loaded);
  - Vendor cannot act on another Store's Order; Customer cannot confirm another Customer's Order or an unshipped one;
  - `delivered_at` set once; Customer confirmation first, then Vendor delivery refused with nothing overwritten, and the reverse;
  - the funds-release job completes a Delivered Order after the Return Window (including one delivered through Customer confirmation) and skips a refunded one;
  - repeated submission of each action changes nothing the second time;
  - full refund: Ledger reversed, Order Refunded, Customer and Vendor notified, refund recorded;
  - partial refund: proportional Commission, status unchanged, balances still reconcile, several partials stop at the paid total, over-refund refused;
  - refund while funds Pending and after release (draws on the right balance);
  - refund of a Completed Order refused without override and reason, allowed with them and audited;
  - restock: off leaves stock alone, on returns only the chosen quantities, full refund returns all;
  - refund of a Cancelled or unpaid Order refused;
  - duplicate refund submission refunds once;
  - force status change requires a reason, writes an audit entry, and refuses Refunded and Paid targets;
  - Reconciliation stays at zero after refund scenarios;
  - Customer Order page shows timeline, tracking details and the confirm button only when appropriate, and contains no Commission or Ledger figure;
  - packing slip renders in Arabic right-to-left with the address and without payment details;
  - a suspended Store's in-flight Orders can still be fulfilled;
  - isolation: Store A's Vendor cannot see or act on Store B's shipments or refunds.
- **Prior art.** Phase 6 Order and state machine tests; Phase 7 Admin and Vendor panel tests; Phase 8 release and reconciliation tests and the Phase 1 isolation helper.
- **Gate.** PHPUnit and formatting pass; the front-end build passes for the Order page change.

## Out of Scope

- Shipping gateway integration and automatic tracking (V2).
- A returns workflow beyond refund; goods-return tracking; the Customer requesting a refund.
- Split shipments and several shipments per Order.
- Payouts and reports (Phase 10); the Satisfaction Survey (later phase).
- Refund of the Shipping Fee on its own, unless included in a partial amount chosen by the Admin.
- Money back to the Customer's account: the Admin transfers it manually and records it, as with Payouts.

## Further Notes

- **Tracker.** The repo has no remote tracker configured, so, as in earlier phases, this spec lives in `docs/specs/` and tickets go to `.scratch/phase-9-shipping-delivery-refunds/issues/` as `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real one.
- **Seams to confirm.** A single seam (panels, storefront routes, scheduled command). Say so if you want a unit seam for refund arithmetic.
- **Suggested ticket order.** Shipment model and shipping contract; transition actions (start, ship, deliver, confirm) with policy; Vendor panel actions and status tabs; Storefront Order page data, confirm route and theme contract bump; refund model and action with ledger reversal; restock stock operation; Admin refund and force-change actions with audit; Shipments relation manager; notifications; packing slip PDF; isolation and phase gate.
- **Open assumptions.** One Shipment per Order; a partial refund keeps the Order's status; Completed refunds need an override and reason; forced changes cannot create Refunded or Paid; Customer notifications are in-app/mail through the existing notification channel; restock quantities are chosen per item on partial refunds.
- **Needs approval before build.** A PDF library for the packing slip (dependency change) and a new stock operation for returning committed stock.
- **Dependency.** Phase 8 is complete and committed (`16c2a6d`), so this phase can start.
