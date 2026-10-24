# Phase 6: Cart, Checkout & Orders

**Status:** ready-for-agent
**Prerequisite:** Phases 1–5 closed. Source: `docs/project-phases-ai-prompts.md`, Phase 6; `docs/adr/0002-reservation-timer-pauses-during-payment-review.md`. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

A Customer can browse a Store and keep an address book, but cannot buy anything: there is no Cart, no Checkout and no Order. Stock cannot be held for a purchase, Commission is not recorded anywhere, a Vendor cannot say what delivery costs, and neither the Platform Admin nor the Vendor can see an Order. Every later phase (payment, ledger, shipping, refunds) needs an Order that already exists with frozen prices, a frozen Commission and a reserved Stock.

## Solution

A visitor adds Variants to a Cart in one Store, which survives signing in. A signed-in Customer checks out in three steps (address, delivery and payment method, review) and places an Order. Placing the Order freezes names, prices, address, contact details and Commission, resolves the Shipping Fee from the Vendor's rates, reserves Stock for the Reservation Window, and leaves the Order awaiting payment. An unpaid Order is cancelled automatically when the window ends and its Stock is released; an Order already under Payment Review is never expired. A Customer can see their Orders and cancel one that is still unpaid. The Platform Admin sees and can cancel Orders across Stores; the Vendor sets Shipping Fees and will see only paid Orders.

## User Stories

1. As a visitor, I want to add a Variant to my Cart without signing in, so that I can shop before deciding to register.
2. As a visitor, I want to choose a quantity when I add a Variant, so that I can buy more than one.
3. As a visitor, I want adding to be refused when the quantity is more than is available, so that I never plan to buy stock that does not exist.
4. As a visitor, I want adding to be refused for a Product that is not published, hidden or in an inactive Category, so that I cannot buy what is not on sale.
5. As a visitor, I want adding to be refused while the Store is Suspended, so that a paused Store takes no new sales.
6. As a visitor, I want adding the same Variant again to increase its quantity, so that my Cart has one line per Variant.
7. As a visitor, I want to change a line's quantity, so that I can correct what I want.
8. As a visitor, I want to remove a line, so that I can drop something I no longer want.
9. As a visitor, I want the Cart to show each line's current price, the line total and the subtotal, so that I know what I will pay for goods.
10. As a visitor, I want the header to show how many items are in my Cart, so that I always see it.
11. As a visitor, I want a Cart that never mixes Stores, so that each Store's basket stays its own.
12. As a visitor, I want my guest Cart to move into my Customer Cart when I log in or register, so that I do not lose it.
13. As a visitor, I want merging to combine quantities of the same Variant without exceeding available stock, so that merging never creates an impossible Cart.
14. As a visitor, I want a line whose Variant became unavailable or whose price changed to be flagged in my Cart, so that I am not surprised at Checkout.
15. As a visitor, I want to be told when I am sent to log in from Checkout, and brought back to Checkout afterwards, so that I can complete my purchase.
16. As a Customer, I want Checkout to need an account, so that my Order is tied to me.
17. As a Customer, I want Checkout to refuse an empty Cart, so that I cannot place an empty Order.
18. As a Customer, I want to choose a delivery address from my address book or add a new one inside Checkout, so that I do not leave the flow.
19. As a Customer, I want to see the Shipping Fee for my chosen address before I confirm, so that I know the full total.
20. As a Customer, I want the Shipping Fee to be the Vendor's rate for my governorate, or the Vendor's flat rate when there is no specific one, so that the fee is fair and predictable.
21. As a Customer, I want a clear message when the Vendor delivers nowhere I can choose, or has not set up delivery yet, so that I understand why I cannot continue.
22. As a Customer, I want to choose Vodafone Cash or InstaPay as my payment method, so that I tell the Platform how I will pay.
23. As a Customer, I want a review step with items, address, fee, total and payment method before I confirm, so that I can check everything.
24. As a Customer, I want confirming to place the Order in one step that either fully succeeds or leaves nothing behind, so that I never end up with half an Order.
25. As a Customer, I want the Order refused with the exact lines that are out of stock, so that I can fix my Cart.
26. As a Customer, I want my Order's prices, product names, SKU, size, colour, address and contact details frozen at that moment, so that later changes to the catalogue do not change my Order.
27. As a Customer, I want my Cart emptied once the Order is placed, so that I do not buy twice by accident.
28. As a Customer, I want a confirmation page with my Order number and a place for payment instructions, so that I know what to do next.
29. As a Customer, I want my Order's stock held for the Reservation Window, so that what I ordered is still there when I pay.
30. As a Customer, I want to see when my unpaid Order will expire, so that I can pay in time.
31. As a Customer, I want a list of my Orders with status badges, so that I can see where each stands.
32. As a Customer, I want an Order detail page with items, address, fee, total and status, so that I can review it.
33. As a Customer, I want to cancel my Order while it is still waiting for payment, so that I can change my mind and free the stock.
34. As a Customer, I want to be unable to cancel an Order once I have submitted payment, so that a paid Order is handled by the Platform.
35. As a Customer, I want to be unable to see or cancel another Customer's Order, or any Order of another Store, so that purchases stay private.
36. As a Customer, I want an unpaid Order cancelled automatically when its window ends, so that I am not committed to something I did not pay for.
37. As a Customer, I want an Order that is under Payment Review never to be cancelled by the timer, so that a slow review cannot cancel an Order I paid for.
38. As a Vendor Owner, I want to set one flat Shipping Fee for my Store, so that delivery is simple.
39. As a Vendor Owner, I want to set a different fee per governorate, so that far places cost more.
40. As a Vendor Owner, I want a flat fee to apply to governorates I have not listed, so that I need not list all 27.
41. As a Vendor Owner, I want invalid fees (negative, not a number) refused, so that I cannot break pricing.
42. As a Vendor Owner, I want my Shipping Fees to belong entirely to me, with no Commission taken on them, so that delivery money is mine.
43. As a Vendor Owner, I want an Orders list that shows only paid Orders and later, so that I never act on an Order that may not be paid.
44. As a Vendor Owner, I want to see only my own Store's Orders, so that other Stores' sales stay private.
45. As a Platform Admin, I want a list of all Orders across Stores filtered by Store, status and date, so that I can oversee sales.
46. As a Platform Admin, I want an Order's page to show its items, totals, address, payment method and a timeline of every status change, so that I can understand its history.
47. As a Platform Admin, I want to cancel an Order that is unpaid or under review with a reason, so that I can resolve problems; the Stock is released.
48. As a Platform Admin, I want a cancelled Order to record who cancelled it, when and why, so that it is auditable.
49. As a Platform Admin, I want Orders of a Suspended Store to keep progressing and expiring normally, so that suspension harms no one with an Order in flight.
50. As a Platform Admin, I want the Commission Rate on each Order Item frozen when the Order is placed, so that later rate changes never rewrite history.
51. As a Platform Admin, I want the rate to be the Store's own override or else the Platform default, so that negotiated deals are honoured.
52. As a Platform Admin, I want each item's Commission Amount rounded half-up to a whole piaster, so that the maths is predictable.
53. As a Platform Admin, I want Commission calculated on goods only, never on the Shipping Fee, so that it matches the agreed rule.
54. As a developer, I want every Order status change to go through one place with explicit allowed transitions, so that an invalid change is impossible.
55. As a developer, I want every transition logged with its actor and note, so that history is complete.
56. As a developer, I want an Order number unique within a Store and generated without gaps or collisions under concurrent orders, so that numbers are reliable.
57. As a developer, I want placing an Order to announce that it happened, and cancelling one to announce that, so that later phases can react.
58. As a developer, I want the expiry job to be safe to run repeatedly and to handle one Order's failure without stopping the rest, so that it is dependable.
59. As a developer, I want the theme contract extended additively for the new pages and data, so that Themes keep working.
60. As a developer, I want the isolation helper to cover every new Store-owned record, so that the Phase 1 guarantee keeps holding.

## Implementation Decisions

- **Modules touched.** A new Sales module owns Carts, Orders, Order Items, status logs, Shipping Rates, the Order state machine, Order placement, expiry and the Admin and Vendor Order resources. Storefront gains Cart, Checkout, confirmation and Order pages and their page data and depends on Sales. Auth is unchanged; the guest-Cart merge reacts to the Customer login event from the Sales side, so Auth never depends on Sales. Dependency direction stays one-way.
- **Cart.** One Cart per Store per visitor: a guest Cart is keyed by the session, a Customer Cart by the Customer. Lines are unique per Variant. Adding and updating validate that the Variant's Product is publicly visible, the Store can trade (Active) and the quantity does not exceed available stock (stock minus reserved). Lines show live prices; a line whose Variant is no longer buyable, or whose price changed since it was added, is flagged and must be fixed before Checkout. On login or registration the guest Cart is merged into the Customer Cart: quantities add up, capped at available stock, and the guest Cart is removed. A Cart never holds Stock; only an Order does.
- **Checkout.** Requires a signed-in Customer (the Phase 5 intended-address mechanism returns them to Checkout). Three steps: address (choose or add, reusing the address book), delivery and payment (the resolved Shipping Fee and a choice of Vodafone Cash or InstaPay, recorded on the Order only; the Platform's payment accounts and instructions are Phase 7), and review. Checkout is refused for a Suspended Store, an empty Cart, or a Cart with flagged lines.
- **Shipping Rates.** Per Store, either one flat rate or per-governorate rates. Resolution: the governorate-specific rate if there is one, otherwise the flat rate, otherwise the Store cannot deliver there and Checkout says so. A Store with no rates at all cannot complete Checkout. Rates are whole piasters, zero or more. The Shipping Fee is the Vendor's alone; it carries no Commission.
- **Placing an Order.** One transaction, either all or nothing: re-validate the Cart and stock, resolve the fee, snapshot each item (product name, SKU, size, colour, unit price, quantity, line total) and the delivery address and Customer contact details, compute Commission per item, reserve Stock through the existing stock service for each item, set the expiry to now plus the Platform's Reservation Window, create the Order as pending payment, log the first status, empty the Cart, and announce that an Order was placed. If any line cannot be reserved, nothing is created or held and the response names the failing lines.
- **Money.** Whole piasters throughout, currency EGP. Subtotal is the sum of line totals, total is subtotal plus Shipping Fee.
- **Commission.** Rate = the Store's own override if set, otherwise the Platform default at the moment of placing. Amount per item = line total × rate ÷ 100, rounded half-up to a whole piaster, computed per item (not on the sum) and frozen on the Order Item together with the rate. Shipping Fee is excluded.
- **Order number.** Unique within a Store, sequential, with a fixed prefix, generated under a lock so concurrent Orders cannot collide.
- **Status flow.** An explicit table of allowed transitions: pending payment → payment review or cancelled; payment review → paid, back to pending payment, or cancelled; paid → processing or refunded; processing → shipped or refunded; shipped → delivered or refunded; delivered → completed or refunded; completed → refunded; cancelled and refunded are final. Everything else throws. Each transition writes a log row (from, to, actor type and id, note) in the same transaction. In this phase only these transitions are exposed: Customer cancel and automatic expiry (from pending payment), and Admin cancel (from pending payment or payment review); the rest are used by later phases. Cancelling releases the held Stock.
- **Expiry.** A scheduled command, every five minutes, cancels Orders that are pending payment and past their expiry, releases their Stock, logs it as the system and announces the cancellation. Orders under Payment Review are never touched (ADR 0002). It processes each Order independently so one failure does not stop the rest, works across all Stores including Suspended ones, and is idempotent. The command visits Stores one at a time inside that Store's context, since no code outside the Admin Panel may bypass the Store filter.
- **Customer visibility.** A Customer sees and cancels only their own Orders in the current Store, through a Policy; another Customer's Order, or another Store's, is simply not found. Customer cancel is allowed only while pending payment.
- **Theme contract.** Raised by a minor step. Cart, Checkout and Orders carry real data; two optional pages are added (order confirmation, order detail) with the placeholder payment-instructions area; the shell's cart count becomes real. The written contract is updated in the same change. Pages added to the contract stay optional so existing Themes keep working.
- **Admin Panel.** A read-mostly Order resource across Stores: filters for Store, status and date, a view page with items, totals, address, payment method and the status timeline, and a Cancel action requiring a reason. Platform Settings already holds the default Commission Rate and Reservation Window.
- **Vendor Panel.** A Shipping Rates page for the Vendor's own Store, and an Order resource limited to the Vendor's own Store and to orders from paid onward (paid, processing, shipped, delivered, completed, refunded). It is empty until Phase 7 produces paid Orders; the scope is implemented and tested now with factory-made Orders.
- **Events.** Placing and cancelling announce events that later phases listen to; this phase adds no listeners beyond the Cart merge.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: given this request, panel interaction, console run or actor, what response, stored Order, Stock level, log or page results. Do not assert call order, class structure or cache keys.
- **Seam 1: the HTTP, panel and console boundary (primary).** Feature tests through the Storefront routes, the Admin and Vendor Panels and the expiry command. Cases:
  - Cart: add, update, remove, merge on login (including caps and same-Variant sums), stock limits, visibility rules, Suspended Store refusal, one Store per Cart, flags for unavailable or re-priced lines, header count;
  - Checkout: login required and return to Checkout, empty or flagged Cart refused, Shipping Fee resolution (specific, flat fallback, none), no rates set up, payment method required;
  - Placing an Order: the happy path produces the frozen snapshot, the Commission, the reserved Stock, the expiry, the first log row and an empty Cart; an out-of-stock line aborts everything and names the lines; a concurrent attempt on the last unit yields exactly one Order; Order numbers are unique and sequential per Store;
  - Customer: sees only their own Orders, cannot see or cancel another's or another Store's, can cancel only while unpaid, and cancelling releases Stock;
  - Expiry: past-due unpaid Orders are cancelled and Stock released; not-yet-due and under-review Orders are untouched; running twice changes nothing; a Suspended Store's Orders are still processed; one failing Order does not stop the others;
  - Admin: list filters, view page with timeline, Cancel with reason releasing Stock and recording the actor, refusal for states that may not be cancelled;
  - Vendor: Shipping Rates validation and save; the Order list shows only paid-onward Orders of the own Store (proved with factory Orders in every status), nothing from other Stores.
- **Seam 2: pure units.** Commission arithmetic (rate source, per-item half-up rounding, exclusion of the Shipping Fee, edge values) and the state machine's allowed and refused transitions are value-in/value-out and tested directly. These are the only unit-level tests, justified by the number of edge cases.
- **Concurrency note.** The test database is in-memory SQLite, which cannot run parallel requests or honour row locks; the last-unit and Order-number tests replay the race with stale copies in one process, and that limitation should be stated in the tests as in Phase 3.
- **Isolation.** Reuse the Phase 1 isolation helper for Carts, Cart lines, Orders, Order Items, status logs and Shipping Rates.
- **Front-end gate.** The type check and production build must pass.
- **Framework and gate.** PHPUnit, no new test dependency; formatting and tests must pass.
- **Prior art.** The Phase 3 stock service tests and Catalog panel tests, the Phase 4 theme rendering tests, and the Phase 5 customer flow, address and panel tests are the templates.

## Out of Scope

- Payment: Receipts, Payment Attempts, Payment Review, the Platform's payment accounts and instructions, and the shorter retry window after a rejection (Phase 7). Only the chosen method is recorded.
- The ledger, Commission settlement, payouts and any money movement (Phases 8–10).
- Shipments, courier and tracking, marking shipped or delivered, the Return Window and completion (Phase 8 onward); refunds (later phase).
- Discounts, coupons, taxes, gift cards, multiple currencies and guest Checkout.
- Order emails, push notifications and the satisfaction survey.
- Editing an Order after it is placed, partial cancellation, and reordering.
- Cart abandonment emails and saved-for-later lists.

## Further Notes

- **Tracker.** As in earlier phases, no tracker is configured and the repo has no git remote, so this spec lives in `docs/specs/` and tickets go to `.scratch/phase-6-cart-checkout-orders/issues/` with `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real tracker.
- **Seams to confirm.** One primary seam (HTTP, panel and console boundary) plus a small pure-unit seam for Commission arithmetic and the state machine, and the existing isolation helper. Say so if you would rather test those two through the primary seam only.
- **Suggested ticket order.** Sales module and Cart with merge; Shipping Rates and the Vendor page; Commission calculator and state machine with logging; Order numbers; PlaceOrder with stock reservation; Checkout pages; Orders list, detail and Customer cancel; expiry command and schedule; Admin Order resource with Cancel; Vendor Order resource scope; theme contract bump and document; phase gate.
- **Open assumptions.**
  - A Store with no Shipping Rates cannot complete Checkout (rather than defaulting to free delivery).
  - Admin cancel is limited to pending payment and payment review in this phase, because cancelling a paid Order needs refunds.
  - The Vendor sees paid, processing, shipped, delivered, completed and refunded Orders, and never cancelled ones.
  - The Order number prefix is fixed (for example ORD) and numbering is per Store.
  - A price change between adding to the Cart and Checkout is shown and must be accepted by re-confirming, rather than silently applied.
  - Guest Carts expire with the session; there is no purge job in this phase.
