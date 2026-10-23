---
status: accepted
---

# The reservation timer pauses during payment review

Unpaid orders expire after the reservation window (24h) and release their stock. Once a Customer has submitted a Receipt, expiry pauses until a Platform Admin decides, so a slow review cannot cancel an order the Customer has already paid for. On rejection the order returns to pending payment with a new, shorter window rather than a fresh 24h, so repeated resubmission cannot hold stock indefinitely.

## Considered Options

- Keep the original 24h timer running through review: simple, but can cancel a paid order and force a refund.
- Restart a full 24h window on every rejection: customer-friendly, but lets stock be held for days.

## Consequences

- The shorter window is the `payment_retry_window_hours` setting (default 6), and an Order allows three Receipt submissions in total.
- Stock can stay reserved for as long as a review is pending. There is no auto-cancel; the Admin dashboard escalates at the 4-hour SLA and flags the review overdue at 72 hours.
