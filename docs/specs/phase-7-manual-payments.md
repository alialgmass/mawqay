# Phase 7: Manual Payments

**Status:** ready-for-agent
**Prerequisite:** Phases 1–6 closed. Source: `docs/project-phases-ai-prompts.md`, Phase 7; `docs/adr/0002-reservation-timer-pauses-during-payment-review.md`. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

A Customer can place an Order but cannot pay for it: the confirmation page has only a placeholder where payment instructions should be, there is nowhere to send proof of a Vodafone Cash or InstaPay transfer, and every Order simply expires after the Reservation Window. The Platform Admin has no queue in which to check transfers, so no Order can ever become paid, a Vendor never sees a sale, and nothing downstream (ledger, shipping) can start.

## Solution

After placing an Order the Customer sees exactly which Platform account to transfer to, the exact amount, the Order reference and how long the reservation lasts, and uploads a Receipt. The Order moves to Payment Review and its expiry timer pauses. A Platform Admin works a review queue: opens the Receipt, compares it with the Order, and approves or rejects. Approval marks the Order paid and turns the held Stock into a sale; rejection returns the Order to awaiting payment with a shorter window so the Customer can try again, up to three submissions in total. Receipts stay private to Platform Admins and the owning Customer, Customers and Admins are notified at each step, and the Vendor learns when a paid Order arrives without ever seeing a Receipt.

## User Stories

1. As a Customer, I want to see the Platform's account for my chosen payment method (name and number or handle) with copy buttons, so that I transfer to the right place without retyping.
2. As a Customer, I want the exact amount to transfer shown, so that I pay the right total.
3. As a Customer, I want my Order number shown as the payment reference, so that the Platform can match my transfer.
4. As a Customer, I want to see how much time is left before my Order expires, so that I pay in time.
5. As a Customer, I want clear written instructions for my chosen method, so that I know the steps.
6. As a Customer, I want to upload a photo or PDF of my transfer receipt, so that the Platform can verify my payment.
7. As a Customer, I want to see a preview of the file before I send it, so that I know I chose the right one.
8. As a Customer, I want to see upload progress, so that I know it is working.
9. As a Customer, I want to optionally add the transfer reference and the number I sent from, so that my payment is easier to match.
10. As a Customer, I want a file refused if it is not a JPG, PNG, WebP or PDF, or is larger than 5 MB, with a clear message, so that I can fix it.
11. As a Customer, I want a file that only pretends to be an image or PDF refused, so that nobody can upload something harmful.
12. As a Customer, I want a confirmation that my Receipt was received, so that I know to wait.
13. As a Customer, I want my Order to show it is under review, so that I know it is being checked.
14. As a Customer, I want my reservation to stay held while my Receipt is under review, so that a slow review cannot cost me my items.
15. As a Customer, I want to be told when my payment is approved, so that I know my Order is confirmed.
16. As a Customer, I want to be told when my payment is rejected, with the reason, so that I know what to fix.
17. As a Customer, I want a rejected Order to give me a new, shorter window to try again, so that I am not stuck but stock is not held forever.
18. As a Customer, I want to see how many attempts I have left, so that I can be careful.
19. As a Customer, I want to upload a new Receipt after a rejection while attempts remain, so that I can correct a mistake.
20. As a Customer, I want a clear message when my third Receipt is rejected that no more attempts are possible, so that I contact the Platform instead.
21. As a Customer, I want to be unable to submit a Receipt for an Order that has expired or been cancelled, so that I do not pay for something that is gone.
22. As a Customer, I want to be unable to submit a Receipt for someone else's Order, so that orders stay private.
23. As a Customer, I want to be unable to submit a second Receipt while one is under review, so that review stays orderly.
24. As a Customer, I want to view my own Receipt later through a temporary link, so that I can check what I sent.
25. As a Customer, I want my Receipt never to be reachable by anyone else, so that my financial details stay private.
26. As a Platform Admin, I want a review queue of Receipts waiting, oldest first, so that no one waits unfairly.
27. As a Platform Admin, I want a count of waiting Receipts on the menu, so that I notice new work.
28. As a Platform Admin, I want to open the Receipt image or PDF inside the queue, so that I can inspect it without downloading.
29. As a Platform Admin, I want the Order total shown next to the amount that should have been transferred, so that I can compare at a glance.
30. As a Platform Admin, I want to see the payment method, sender number, transfer reference and attempt number, so that I can match the transfer to the account.
31. As a Platform Admin, I want a warning when the same transfer reference was used before, anywhere on the Platform, so that I can spot reused receipts.
32. As a Platform Admin, I want a duplicate flag to warn but never block, so that I decide with the facts.
33. As a Platform Admin, I want to approve a Receipt, so that the Order becomes paid.
34. As a Platform Admin, I want to reject a Receipt with a required reason, so that the Customer knows what to fix.
35. As a Platform Admin, I want approving twice to do nothing the second time, so that a double click or retry cannot repeat the effects.
36. As a Platform Admin, I want my name and the time recorded on every decision, so that reviews are auditable.
37. As a Platform Admin, I want an approved Order's reserved Stock turned into a sale, so that inventory is right.
38. As a Platform Admin, I want to be unable to approve a Receipt whose Order was cancelled, so that I cannot pay for a dead Order.
39. As a Platform Admin, I want to still be able to cancel an Order whose third Receipt was rejected, so that stock is freed.
40. As a Platform Admin, I want a widget of Receipts waiting longer than the review target, with those past the overdue threshold marked, so that slow reviews are visible.
41. As a Platform Admin, I want reviews never to cancel Orders automatically, so that a paid Customer is never penalised for my backlog.
42. As a Platform Admin, I want to manage the Platform's Vodafone Cash and InstaPay accounts, so that the instructions Customers see are always right.
43. As a Platform Admin, I want to switch an account off, so that Customers are not sent to a retired number.
44. As a Platform Admin, I want to be told when a new Receipt arrives, so that I can review it promptly.
45. As a Platform Admin, I want a Customer's Order to keep the instructions that applied when it was placed available while the account is switched off, so that a transfer in progress can still be matched.
46. As a Vendor Owner, I want to be notified when an Order of mine is paid, so that I can start preparing it.
47. As a Vendor Owner, I want paid Orders to appear in my Orders list now, so that I can see my sales.
48. As a Vendor Owner, I want to see only that an Order is paid, never the Receipt or any payment details, so that the Customer's financial details stay with the Platform.
49. As a Vendor Owner, I want a Receipt link never to work for me, even if I am given one, so that privacy does not depend on secrecy of the link.
50. As a Customer, I want my notifications in Arabic, so that I understand them.
51. As a developer, I want a payment gateway contract with a manual implementation, so that an online gateway can be added later without rewriting Orders.
52. As a developer, I want approval to announce that a payment was approved exactly once, so that the ledger can react in a later phase.
53. As a developer, I want Receipts kept on private storage in a per-Store, per-Order folder, so that they are never publicly addressable.
54. As a developer, I want images re-encoded on upload, so that hidden data and malicious payloads are stripped.
55. As a developer, I want the duplicate check to look across Stores without exposing one Store's data to another, so that tenant isolation holds.
56. As a developer, I want the isolation helper to cover every new Store-owned record, so that the Phase 1 guarantee keeps holding.

## Implementation Decisions

- **Modules touched.** A new Payments module owns Payment Methods, Payments (the Customer's claim with its Receipt), receipt storage, review actions, notifications and the gateway contract. Sales exposes the Order state machine and stock service that Payments calls. Storefront gains the payment parts of the confirmation and Order pages. Backoffice-facing resources live in Payments' own plugin. Dependency direction stays one-way: Payments depends on Sales, Catalog, Auth and Tenancy; Sales does not depend on Payments.
- **Platform Payment Methods.** A Platform-level table with no Store ownership: type (Vodafone Cash or InstaPay), account name, account number or handle, free-text instructions and an active flag. Customers see the active account of the type they chose at Checkout. If no active account exists for that type, the confirmation page says payment details are not available and the Order is left to expire normally. An Order keeps showing the account it was placed against (a snapshot of the method's details is stored on the Payment when the first Receipt is submitted).
- **Payments.** Store-owned: Order, method type, amount (the Order total in whole piasters), status (pending review, approved, rejected), receipt file path, optional transfer reference and sender number, attempt number, reviewer and time, rejection reason, and a duplicate pointer. One row per submission, so an Order has up to three.
- **Submitting a Receipt.** Allowed only for the signed-in Customer's own Order that is pending payment, whose reservation has not run out (even if the expiry job has not yet reached it), with no Receipt currently under review and fewer than three submissions so far. Files: JPG, PNG, WebP or PDF up to 5 MB; type is decided by inspecting the content, not the name or the declared type. Images are decoded and re-encoded in the same format so metadata and embedded payloads are dropped; PDFs must begin with the PDF signature and are stored as they are. Anything else is refused. The file goes to private storage under a per-Store, per-Order folder with a random name. The optional sender number is normalised like any other Egyptian mobile number. On success the Order moves to payment review (logged) and its expiry no longer applies while it stays there. The Customer and the Platform Admins are notified.
- **Attempts.** Three submissions in total per Order. After the third is rejected the Customer cannot submit again; the Order is left to expire on its shorter window, and a Platform Admin can still cancel it.
- **Duplicate detection.** A transfer reference, normalised (trimmed, case-folded, separators removed), is looked up in a small Platform-level index that holds only the reference's fingerprint and the id of the first Payment that used it, so Stores can be compared without any Store reading another's rows. A later Payment with the same fingerprint stores a pointer to the first and is flagged; it is never blocked. Admins see the flag.
- **Approving.** One transaction, idempotent and locked: the Payment must be pending review and its Order in payment review; the Payment is marked approved with reviewer and time; the Order moves to paid through the state machine; each Order Item's reserved Stock is committed (stock and reservation both reduced); and exactly one payment-approved event is announced after commit. A second approve of the same Payment changes nothing and announces nothing. Approving a Payment whose Order has since been cancelled is refused.
- **Rejecting.** One transaction, idempotent and locked, with a required reason: the Payment is marked rejected with reviewer, time and reason; the Order moves back to pending payment; its expiry is set to now plus the Platform's payment retry window (default 6 hours), not a fresh Reservation Window; the Customer is notified with the reason. A rejected Payment cannot later be approved; a new submission is required.
- **Receipt access.** Only through temporary signed links, valid for a few minutes, issued to Platform Admins and to the Order's own Customer. The link identifies the Store and the Payment and also requires that the person opening it is a Platform Admin or that Customer, so a leaked link does not help anyone else. Vendors and other Customers are refused. The file is streamed from private storage with private caching headers.
- **Notifications.** Queued, stored in the database, and mailed to the Customer and Platform Admins; Vendor Owners receive a database notification only. Customer: receipt received, approved, rejected with reason. Admins: a new Receipt is waiting. Vendor: a new paid Order. Text is Arabic in the language files, with English counterparts.
- **Review SLA.** The Platform settings for the review target (default 4 hours) and the overdue threshold (default 72 hours) drive a dashboard widget. Reviews never cancel anything; they only escalate.
- **Gateway contract.** A payment gateway interface with a manual implementation that records a claim and awaits review. No online gateway is built.
- **Theme contract.** Raised by a minor step. The Order detail and confirmation data gain the payment block (status, attempts used and left, whether a Receipt can be submitted now, rejection reason, the account details to transfer to, the exact amount, the reference and the expiry, and a temporary link to the Customer's own latest Receipt). The payment instructions component is filled in, including copy buttons, preview, progress and the upload form. Existing Themes keep working.
- **Admin Panel.** A Payment review queue: default filter pending review, oldest first, a menu badge with the waiting count, a receipt preview modal for images and PDFs through a signed link, the Order amount beside the expected amount, a duplicate badge, and Approve and Reject (reason required) actions. A widget lists Receipts waiting longer than the review target with an overdue flag. A Payment Method resource manages the Platform's accounts. The Admin Order page shows the payment history.
- **Vendor Panel.** No Receipt or payment detail is shown; paid Orders now appear through the Phase 6 scope, and the paid-Order notification is delivered.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies (ADR 0001); PHPUnit replaces Pest wording.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: given this request, panel interaction or actor, what response, stored Payment, Order status, Stock level, notification, event or file access results. Do not assert call order, class structure or storage paths beyond "private".
- **Seam: the HTTP and panel boundary (the only seam).** Feature tests through the Storefront routes, the Admin and Vendor Panels and the signed receipt link. Cases:
  - submitting: success for the own unpaid Order (Order moves to review, Customer and Admins notified, timer no longer applies); refused for another Customer's Order, another Store's Order, a non-pending Order, an expired Order whose job has not yet run, a cancelled Order, a second Receipt while one is under review, and after three attempts;
  - files: allowed types accepted; wrong type, oversize, a renamed non-image, a PDF without the signature, an SVG and a GIF refused; images are re-encoded (metadata removed); stored on private storage under the Store and Order folder, never publicly reachable;
  - optional fields: transfer reference and sender number saved and normalised; both may be omitted;
  - approve: Order becomes paid, Stock committed, event announced exactly once, decision recorded, second approve does nothing, refused when the Order was cancelled, Vendor notified;
  - reject: reason required, Order back to pending payment with the shorter window (not a fresh one), Customer notified with the reason, resubmission possible while attempts remain, third rejection is final, Admin can still cancel, rejected Payment cannot be approved;
  - expiry interplay: an Order under review is not expired by the command; after rejection the shorter window applies and the Order then expires normally;
  - duplicates: a repeated reference across two Stores is flagged on the later Payment without blocking and without revealing any Store's data to the other Store's users;
  - receipt links: valid for an Admin and for the owning Customer; refused for a Vendor, another Customer, a guest, an expired link and a tampered link; a Vendor holding a valid link is still refused;
  - Admin queue: oldest first, default filter, badge count, amount comparison, duplicate badge, overdue widget, Payment Method management and switching off; Vendor sees paid Orders but no payment data anywhere;
  - Storefront: the confirmation and Order pages show the account, amount, reference, remaining time and payment status in every state, with the account shown being the active one for the chosen method.
- **Isolation.** Reuse the Phase 1 isolation helper for Payments; Payment Methods and the duplicate index are Platform-level and are checked for exposing nothing across Stores.
- **Front-end gate.** The type check and production build must pass.
- **Framework and gate.** PHPUnit, no new test dependency; formatting and tests must pass.
- **Prior art.** The Phase 6 checkout, order and expiry tests; the Phase 5 customer flow tests; the Phase 3 image validation tests; the Phase 4 and 6 Admin and Vendor panel tests.

## Out of Scope

- Ledger entries, Commission settlement and payouts (Phase 8 listens to the payment-approved event).
- Online payment gateways, cards and refunds; only the contract and the manual implementation exist.
- Shipping, delivery and completion of Orders (later phases).
- Refunds and partial payments; a Customer must transfer the exact total in one transfer.
- Automatic reading of receipts (OCR) and bank or wallet integrations.
- Customer-initiated cancellation after a Receipt is submitted.
- SMS and push notifications.

## Further Notes

- **Tracker.** As in earlier phases, no tracker is configured and the repo has no git remote, so this spec lives in `docs/specs/` and tickets go to `.scratch/phase-7-manual-payments/issues/` with `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real tracker.
- **Seams to confirm.** A single seam this time (the HTTP and panel boundary) plus the existing isolation helper; there is no worthwhile pure-unit seam. Say so if you want a unit seam for the file inspection or the reference fingerprint.
- **Suggested ticket order.** Platform Payment Methods and their resource; Payment model and gateway contract; file inspection and private storage; SubmitReceipt with attempts and expiry rules; duplicate index; ApprovePayment and RejectPayment; signed receipt links and authorization; notifications; Storefront payment block and upload form; Admin review queue with badge and widget; Vendor paid-Order notification; contract bump and document; phase gate.
- **Open assumptions.**
  - The duplicate index is a Platform-level table of fingerprints, chosen so the cross-Store check needs no cross-Store query from Store code.
  - Images are re-encoded in their own format; PDFs are checked by signature and stored unchanged.
  - A Receipt link lasts five minutes.
  - The Customer's account details are snapshotted on the first Receipt so later edits to the Platform account do not change what an in-flight Order shows.
  - Vendor notifications are in-app only.
  - An Order with no active account for its chosen method can still be paid by contacting the Platform; the Admin can cancel it.
