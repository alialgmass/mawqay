# Pilot plan

The pilot runs the platform with 2–3 real Vendors and real Customers. It is a controlled test of the business model and the money flow, not a public launch.

## Launch gate (product owner, not a coding task)

**No real Customer money is accepted until every item below is signed off.** The owner of each item is the product owner.

- [ ] **Accountant review** of the money-holding model: the Platform receives every payment, holds the Vendor's share, takes Commission and pays out later. Confirm tax treatment, whether Commission needs an invoice, and what records the Platform must keep.
- [ ] **Lawyer review** of the Vendor terms of use, the Customer terms and the privacy notice, including who is responsible for refunds, returns and goods quality, and how disputes are handled.
- [ ] **Commission invoice** format agreed with the accountant.
- [ ] **Vendor agreement signed** by each pilot Vendor, including the Commission rate, the Return Window, the payout minimum and the payout schedule.
- [ ] **Payment accounts** (Vodafone Cash and InstaPay) opened in the business name, with a named person responsible for reviewing Receipts within the review target.
- [ ] **Deployment checklist** in `DEPLOYMENT.md` complete, including a rehearsed backup restore.
- [ ] **Support contact** published to Customers and Vendors, with who answers and when.

## Vendor onboarding (per Vendor)

1. Platform Admin creates the Store and sends the invitation.
2. The Vendor accepts, signs in and follows the onboarding checklist on their dashboard: logo, first product, Theme, shipping rates, payout details.
3. Admin reviews the Store identity, products and shipping rates before activating the Store.
4. The Vendor places a test Order as a Customer, pays with a small real transfer, and is walked through shipping and delivery.
5. Admin confirms the Vendor understands: Commission, the Return Window, Available vs Pending money, how to request a payout and the minimum.
6. The Store is switched to Active.

## First 20 real Orders: test script

For each of the first 20 real Orders, one person (the pilot owner) follows the Order from start to finish and ticks a row:

| Step | Check |
| --- | --- |
| Placed | Customer received the confirmation; stock reserved |
| Receipt uploaded | Admin sees it in the queue within the review target |
| Approved | Customer and Vendor notified; Order is Paid; Ledger shows cash, Vendor payable and Commission |
| Shipped | Courier and tracking visible to the Customer |
| Delivered | Delivery recorded once; Return Window started |
| Survey | Invitation arrived after the delay; answer recorded |
| Funds | Money moved to Available only after the Return Window |
| Payout | Request, transfer, reference and proof recorded; balances correct |
| Books | `ledger:reconcile` reports no differences |

Any step that does not behave as written is logged as an incident (below) before the next Order is taken.

## What to monitor

Daily during the pilot:

- **System health** page: scheduler, queue, failed jobs, last Reconciliation.
- **Receipts waiting** longer than the review target.
- **Payouts awaiting** transfer.
- **Low Ratings** and any Customer complaint.
- **Audit log**: every forced status change, refund and payout decision has a reason you recognise.
- Uptime monitor on `/health`.

## Incident checklist: payment or Ledger problems

1. **Stop** taking new Orders for the affected Store (suspend it) if money may be wrong.
2. **Do not edit or delete** Ledger entries; they cannot be changed. Corrections are new, opposite entries made through the refund or payout actions.
3. Run `php artisan ledger:reconcile` and save the result.
4. Find the Order and Payment in the Admin Panel and read its status history and the **Audit log** entries around it.
5. Check the Receipt, the transfer reference and the Vendor balances against the bank or wallet statement.
6. Decide the fix with the accountant if money moved incorrectly; make it through the product, never in the database.
7. Tell the affected Customer or Vendor what happened and what you did.
8. Record: what happened, when, how it was found, the fix and the follow-up, in the incident log.

## Success measures

- 40% or more of Customers answer the satisfaction survey.
- Average rating 4.2 out of 5 or better.
- Zero unexplained Reconciliation differences.
- Receipts reviewed within the target.
