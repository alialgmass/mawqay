# Phase 11: Satisfaction Surveys and Notifications

**Status:** ready-for-agent
**Prerequisite:** Phases 1–10 closed (Phase 9 emits `OrderDelivered`; earlier phases already send Arabic queued notifications). Source: `docs/project-phases-ai-prompts.md`, Phase 11, and `docs/REVIEW-ROUND-1.md` R3-2. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

The Platform has no way to learn whether Customers were happy. A Vendor cannot see how their Store is rated, a Platform Admin cannot spot an unhappy Customer before it becomes a complaint, and the success measures the product set (40% response, average 4.2 out of 5) cannot be measured at all. Separately, the Arabic wording of notifications is scattered across several modules, so nobody can list who is told what, wording drifts, and a new event can quietly ship without any message.

## Solution

Two days after an Order is delivered, the Customer receives an Arabic message with a signed link to a short Satisfaction Survey: an overall rating, how well the product matched, how fast delivery was, and an optional comment. It can be submitted once. A Low Rating (overall 2 or below) alerts the Platform Admin and that Store's Vendor Owner, and does nothing else: no refund, no status change. Platform Admins see every Survey with averages, response rate and flagged ones; each Vendor sees only their own Store's feedback. All Arabic notification wording moves into one central catalogue that lists every event, its recipients and its template, with a test that fails if an event has no template.

## User Stories

1. As a Customer, I want to be asked how my Order went a couple of days after it arrives, so that I can tell the Store whether it was good.
2. As a Customer, I want the invitation to arrive by email when I registered with an email, so that I do not have to look for it.
3. As a Customer, I want the link to open the survey directly without logging in, so that answering takes seconds.
4. As a Customer, I want large star inputs for overall, product match and delivery speed, so that I can rate easily on my phone.
5. As a Customer, I want to add an optional comment, so that I can explain a low or high rating.
6. As a Customer, I want a rating of 1 to 5 enforced on each question, so that my answer is meaningful.
7. As a Customer, I want a thank-you page after I submit, so that I know it was received.
8. As a Customer, I want to answer only once, so that my answer is final and cannot be changed by mistake.
9. As a Customer, I want opening the link again after answering to tell me I already responded, so that I am not confused.
10. As a Customer, I want a link that has been altered or has expired to show a clear message instead of an error page, so that I know what happened.
11. As a Customer, I want the survey page in Arabic, right-to-left and in my Store's look, so that it feels like part of the Store.
12. As a Customer, I want to find a pending survey from my Order page when I am logged in, so that I can answer even if I lost the email.
13. As a Customer who registered with a mobile number only, I want a way to receive the link, so that I am not left out.
14. As a Customer, I want my survey to be about the Order I received only, so that I cannot rate someone else's Order.
15. As a Customer, I want my answer never to change my Order or trigger a refund, so that I can be honest without consequences.
16. As a Vendor Owner, I want to see ratings and comments for my own Store's Orders, so that I can improve.
17. As a Vendor Owner, I want average ratings (overall, product match, delivery speed) for the last 30 days, so that I see my standing.
18. As a Vendor Owner, I want a trend chart of my ratings, so that I see whether things improve.
19. As a Vendor Owner, I want flagged (low) surveys highlighted, so that I act on them first.
20. As a Vendor Owner, I want to filter feedback by rating and date, so that I can focus.
21. As a Vendor Owner, I want a notification when one of my Orders gets a Low Rating, so that I can respond quickly.
22. As a Vendor Owner, I want to see only my own Store's surveys, so that other Stores' feedback stays private.
23. As a Vendor Owner, I want to copy a survey link for an Order, so that I can send it myself, for example by WhatsApp, to a Customer who did not get the email.
24. As a Platform Admin, I want a list of every Survey across Stores with filters for Store, rating, date and flagged, so that I can review feedback.
25. As a Platform Admin, I want to open a Survey and see its comment and a link to the Order, so that I can investigate.
26. As a Platform Admin, I want the average rating per Store for 30 days, so that I can compare Stores.
27. As a Platform Admin, I want the response rate (submitted divided by sent), so that I can track the 40% target.
28. As a Platform Admin, I want a list of flagged surveys on the dashboard, so that unhappy Customers are noticed.
29. As a Platform Admin, I want a notification when any Low Rating arrives, so that I can reach out.
30. As a Platform Admin, I want to copy a survey link for any Order, so that I can help a Customer who has no email.
31. As a Platform Admin, I want the survey delay (days after delivery) tuned in Platform Settings, so that I can change it without a release.
32. As a Platform Admin, I want the survey to be independent of the Return Window, so that feedback is not tied to refund timing.
33. As a Platform Admin, I want a Survey created when an Order is delivered but sent only after the delay, so that timing is predictable.
34. As a Platform Admin, I want each Order to have at most one Survey however many times delivery is announced, so that nobody is asked twice.
35. As a Platform Admin, I want each Survey invitation to be sent only once, even if the sender runs twice, so that Customers are not spammed.
36. As a Platform Admin, I want Orders that were refunded or cancelled before the invitation is due to get no invitation, so that we do not ask for feedback on a refunded Order.
37. As a Platform Admin, I want one central place listing every notification event, who receives it and which template it uses, so that I can see what the system says and to whom.
38. As a Platform Admin, I want all notification wording to be Arabic and in one catalogue, so that tone and terms stay consistent.
39. As a Platform Admin, I want a failing test if an event has no template, so that no event ships silent.
40. As a Platform Admin, I want notifications to be queued and sent by mail and in-app where the recipient has an email, so that a slow mail server never slows a page.
41. As a Platform Admin, I want to be notified of a new payout request, so that I do not rely on checking the queue.
42. As a Customer, I want to be told when my Order is placed, so that I have confirmation.
43. As a developer, I want a channel interface so that a WhatsApp channel can be added later without touching events or templates, so that growth is cheap.
44. As a developer, I want a Low Rating event that only notifies, so that no listener can accidentally refund or change a status.
45. As a developer, I want signed links to be generated in one place, so that expiry and tamper rules are identical everywhere.
46. As a developer, I want the isolation helper to cover Surveys, so that tenant isolation keeps holding.

## Implementation Decisions

- **Modules.** A new Feedback module (depends on Sales and Tenancy) owns Surveys. A new Notifications module (consumes the others, per the module direction) owns the catalogue, the channel interface and the delivery of notifications. Sales, Payments, Shipping and Ledger do not depend on either; they announce events and Notifications listens.
- **Survey.** Store-owned, one per Order (unique by Order). Fields: Store, Order, token, overall rating, product-match rating, delivery-speed rating (each 1–5, empty until answered), comment, sent time, submitted time, low-rating flag. A Survey is created when an Order is delivered (the existing delivered event; creating it again for the same Order changes nothing, including when an Admin forces delivery). Sending happens later.
- **Timing.** A Survey becomes due `survey_delay_days` (existing Platform setting, default 2) after the Order's delivery time. Not tied to the Return Window.
- **Sending.** An hourly command finds due, unsent Surveys and sends the invitation, marking it sent in the same transaction so a repeat run sends nothing. Orders that are Refunded or Cancelled by the time it is due are skipped and never sent. Invitations go by email when the Customer has an email; a Customer with only a mobile number gets none automatically, and the link stays copyable by the Vendor and Admin (see below). A failure for one Survey does not stop the others.
- **Signed link.** The survey link is a signed URL that expires 30 days after it is generated. A tampered or expired link shows a clear Arabic state rather than an error. The copyable link for a Vendor or Admin is generated on demand with the same rules and the same expiry. Opening a valid link never requires login. A rate limit applies to opening and submitting.
- **Submission.** One action validates the three ratings (1–5, all required) and an optional comment (length-limited), refuses a second submission, and stores the answer. It sets the low-rating flag when the overall rating is 2 or below and announces a Low Rating event. The Customer's answer never changes the Order, its status, its money or any refund.
- **Low Rating.** The event notifies exactly the Platform Admins and the Vendor Owners of that Order's Store, in-app and by email where available. Customers and other Stores are never notified. There are no other listeners.
- **Logged-in access.** A logged-in Customer sees a pending survey from their own Order page, for their own Order only, without needing the signed link.
- **Storefront.** A public survey page (valid, already-answered, expired or invalid, and thank-you states) with large star inputs, three ratings and a comment, mobile first and right-to-left, styled by the Store's theme tokens. The Order page data gains the pending-survey link and state. The theme contract's minor version is bumped and its document updated; the default Theme renders the new page and part.
- **Notification catalogue.** One registry lists every event, its recipients and its template. It covers: order placed (Customer), Receipt received, approved and rejected (Customer), Receipt awaiting review (Admin), Order paid (Vendor), shipped and delivered (Customer), refunded (Customer and Vendor), survey invitation (Customer), payout requested (Admin), payout paid and rejected (Vendor), Low Rating (Admin and Vendor) and reconciliation mismatch (Admin). Existing notifications keep their behaviour but take their wording from the catalogue; two events do not exist yet and are added here: order placed and payout requested. All are queued, in Arabic, with mail plus in-app database delivery where the recipient has an email and in-app only otherwise.
- **Channels.** A channel contract with mail and in-app implementations; a WhatsApp channel is not built, but nothing in events or templates may assume a particular channel.
- **Admin Panel.** A read-only Survey resource across Stores with filters (Store, rating, date, flagged) and a view with the comment, the Order link and a copy-link action. Widgets: average rating per Store for 30 days, response rate, flagged list. Platform Settings keeps the survey delay field.
- **Vendor Panel.** A Customer feedback page: own Surveys with filters, average rating widgets, a trend chart, flagged rows highlighted, and a copy-link action on the Order. A Low Rating notification appears in-app.
- **Rating figures.** Averages and response rate count only submitted Surveys in the period (response rate is submitted over sent). Store-scoped figures are cached briefly under the Store's own prefix.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies; PHPUnit replaces Pest wording.

## Testing Decisions

- **What makes a good test.** Assert outcomes only: what is created, sent, shown and to whom, and that the Order is untouched. Do not assert class structure or query shape.
- **Seam: the event, scheduled command, storefront route and panel boundary (the only seam).** Tests announce delivery (or deliver an Order through the existing fulfilment path), run the scheduled command with time moved forward, open the signed link and submit through storefront routes, and drive the Admin and Vendor panels. The catalogue coverage test reads the registry through its public listing.
- **Cases:**
  - a Survey is created on delivery, once per Order, including repeated delivery announcements and a forced delivery;
  - not sent before the delay, sent after it, sent once however often the command runs, one failure does not stop others; the delay setting is honoured; independent of the Return Window;
  - refunded or cancelled Orders get no invitation; a mobile-only Customer gets no email but the link is available to Vendor and Admin;
  - signed link: valid opens, tampered shows the invalid state, expired shows the expired state, other Stores' and other Orders' tokens do not work; the copied link follows the same rules;
  - submission: valid saved; ratings outside 1–5 or missing refused; comment length limited; second submission refused and the first kept; already-answered state; thank-you state;
  - overall 2 or below flags and notifies exactly the Platform Admins and that Store's Vendor Owners, nobody else; overall 3 does not; the Order's status, money and refunds are unchanged afterwards;
  - a logged-in Customer sees the pending survey only for their own Order;
  - rate limit on the public survey routes;
  - Vendor sees only their own Store's Surveys, averages, trend and flagged rows; Admin sees all with working filters; response rate and averages are correct and exclude unanswered Surveys; Store-scoped caches do not leak;
  - catalogue: every listed event has an Arabic template and at least one recipient; adding an event without a template fails the test; existing notifications use catalogue wording; order placed and payout requested notifications are sent to the right recipients;
  - isolation for Surveys.
- **Prior art.** Phase 7 and 9 notification and storefront tests; Phase 9 fulfilment tests for delivery; Phase 10 report, cache and panel tests; the Phase 1 isolation helper.
- **Gate.** PHPUnit, formatting, front-end type check and build pass.

## Out of Scope

- Public product reviews and ratings shown on the Storefront (V2).
- WhatsApp or SMS delivery (only the channel contract is prepared).
- Reminders, follow-up invitations or editing an answer.
- Any automatic action on a Low Rating (refund, status change, payout hold).
- Customer-visible Vendor responses to feedback.
- Resolving Phase 9 and 10 review findings, except where noted below.

## Further Notes

- **Tracker.** The repo has no remote tracker configured, so, as in earlier phases, this spec lives in `docs/specs/` and tickets go to `.scratch/phase-11-satisfaction-surveys-and-notifications/issues/` as `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real one.
- **Seams to confirm.** A single seam (events, scheduled command, storefront routes, panels). Say so if you want a unit seam for the catalogue.
- **Suggested ticket order.** Survey model and creation on delivery; signed link service; send command and invitation; submission and Low Rating event; Storefront survey page and Order page data with contract bump; Notifications catalogue, channel contract and migration of existing wording; new events (order placed, payout requested); Admin Survey resource and widgets; Vendor feedback page and widgets; copy-link actions; isolation and phase gate.
- **Open assumptions.** Customers with only a mobile number are not sent anything automatically; invitations are skipped for Refunded and Cancelled Orders; the link expires 30 days after it is generated, not 30 days after delivery; the catalogue includes two events (order placed, payout requested) that did not exist before.
- **Carry-over from earlier reviews.** Open and unconfirmed from the Phase 10 review: the GMV and top-products figures are bucketed by Order creation date rather than payment date (needs a decision); payout proof accepts images only; the Payout actions have no role check beyond the panel; Payout isolation is not yet in the shared isolation helper; a zero-amount guard is missing in the payout request if the minimum is ever set to 0. The survey rating averages should follow whatever date basis is chosen for reports.
- **Dependency.** Phase 10 is complete and committed (`70f38db`), so this phase can start.
