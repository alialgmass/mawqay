# Phase 5: Customer Accounts

**Status:** ready-for-agent
**Prerequisite:** Phases 1–4 closed. Source: `docs/project-phases-ai-prompts.md`, Phase 5. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

A visitor to a Storefront cannot yet become a Customer in a way they can see: registration, login and logout exist only as raw API endpoints with no pages, no way to recover a forgotten password, no profile, and nowhere to keep delivery addresses. Checkout, the next phases' centrepiece, has nobody to attach an Order or an address to. A Platform Admin and a Vendor Owner also have no view of who their Customers are, and cannot stop a misbehaving Customer from signing in.

## Solution

A visitor registers and signs in inside one Store using an email or a mobile number, recovers a forgotten password by email, manages a profile and a book of Egyptian delivery addresses, and always sees whether they are signed in. Everything lives in the Store's own look through new Theme pages, and a Customer's account and session never leave their Store. A Platform Admin gets a read-only list of Customers across Stores with the ability to deactivate or reactivate one; a Vendor Owner gets a read-only list of their own Store's Customers.

## User Stories

1. As a visitor, I want to register with my name, a password and either an email or a mobile number, so that I can create an account without being forced to give both.
2. As a visitor, I want registration refused if I give neither an email nor a mobile number, so that my account can always be found.
3. As a visitor, I want my email and my mobile number each to be unique within the Store, so that nobody else can hold my identifier.
4. As a visitor, I want to use an email or mobile number that already has an account in another Store, so that shopping in one Store never blocks me in another.
5. As a visitor, I want a mobile number accepted in common Egyptian formats and stored in one standard form, so that I can log in however I typed it.
6. As a visitor, I want clear Arabic validation messages next to the fields, so that I know what to fix.
7. As a visitor, I want to be signed in straight after registering, so that I can carry on.
8. As a Customer, I want to log in with my email or my mobile number plus my password, so that I use whichever I remember.
9. As a Customer, I want a wrong identifier or password to give one generic message, so that nobody can find out which accounts exist.
10. As a Customer, I want login, registration and password-reset requests rate limited, so that my account cannot be guessed at.
11. As a Customer, I want to see a clear message and how long to wait when I am rate limited, so that I am not left guessing.
12. As a Customer, I want to log out, so that nobody else on a shared device can use my account.
13. As a Customer, I want the header to show Log in and Register when I am signed out and my account when I am signed in, so that I always know my state.
14. As a Customer, I want my account and session to work only inside the Store I registered in, so that a login cannot be reused elsewhere.
15. As a Customer, I want to be treated as signed out in a different Store while staying signed in to my own, so that visiting another Store does not end my session.
16. As a Customer, I want to ask for a password reset by email, so that I can get back in if I forget my password.
17. As a Customer, I want the same response whether or not the email has an account, so that the form cannot be used to discover accounts.
18. As a Customer, I want a reset link that works only in the Store that sent it, so that a link from one Store cannot reset another Store's account.
19. As a Customer, I want a reset link to expire and to work once, so that an old email is not a standing key.
20. As a Customer, I want requesting a reset in one Store not to cancel a pending reset I requested in another Store with the same email, so that Stores never interfere.
21. As a Customer, I want my other sessions ended when I reset my password, so that a stolen session stops working.
22. As a Customer, I want the reset email in Arabic and in the Store's name, so that I recognise it.
23. As a Customer who registered with only a mobile number, I want to add an email in my profile, so that I can use password reset.
24. As a Customer, I want to edit my name, email and mobile number, so that my details stay current.
25. As a Customer, I want an edited email or mobile number to stay unique within the Store, so that I cannot take someone else's.
26. As a Customer, I want to keep at least one of email or mobile number, so that my account can still be found.
27. As a Customer, I want to change my password by giving my current one first, so that someone on my unlocked device cannot lock me out.
28. As a Customer, I want a changed password to end my other sessions, so that old devices are signed out.
29. As a Store, I want email verification available behind a setting that is off by default, so that a Store can require it later.
30. As a Customer, when verification is on, I want a verification email and a way to resend it, so that I can finish signing up.
31. As a Customer, I want to add a delivery address with label, recipient name, phone, governorate, city, street address and notes, so that Orders can be delivered to me.
32. As a Customer, I want the governorate chosen from the list of Egyptian governorates, so that addresses are consistent.
33. As a Customer, I want my first address to become my default automatically, so that I do not have to choose.
34. As a Customer, I want to mark any address as my default and have the previous default stop being one, so that exactly one is default.
35. As a Customer, I want to edit an address, so that I can fix a detail.
36. As a Customer, I want to delete an address, so that old places do not clutter my list.
37. As a Customer, I want another address promoted to default if I delete the default one, so that I am never left without one.
38. As a Customer, I want to see only my own addresses, so that my details stay private.
39. As a Customer, I want to be unable to read, change or delete anyone else's address even by guessing its identifier, so that my neighbours' details are safe.
40. As a Customer, I want a limit on how many addresses I can store, so that the list stays usable.
41. As a Customer, I want to be sent to the login page when I open an account page signed out, and brought back to that page after signing in, so that I do not lose my place.
42. As a guest, I want to be brought back to checkout after logging in from checkout once checkout exists, so that I can complete my purchase.
43. As a visitor, I want the return address to be accepted only if it is a page inside the same Store, so that a crafted link cannot send me to another site.
44. As a visitor, I want a signed-in Customer who opens Login or Register to be sent to their account, so that I do not see pointless forms.
45. As a Customer, I want login, register, forgot-password, reset-password, account and address pages in the Store's own Theme, so that the Store feels like one shop.
46. As a Customer, I want those pages right-to-left, mobile first and accessible, so that they work on my phone.
47. As a Customer, I want a deactivated account to be unable to log in, so that the Platform can stop abuse.
48. As a Customer, I want a deactivated account signed out on its next request, so that deactivation takes effect immediately.
49. As a Customer, I want a clear message when my account is deactivated that does not reveal anything else, so that I know to contact the Store.
50. As a Platform Admin, I want a read-only list of Customers across all Stores with Store filter and search by name, email and mobile number, so that I can find anyone.
51. As a Platform Admin, I want to open a Customer and see their details and addresses, so that I can help with a problem.
52. As a Platform Admin, I want to deactivate and reactivate a Customer, so that I can stop abuse and undo it.
53. As a Platform Admin, I want no way to edit a Customer's details or password, so that support actions stay traceable to the Customer.
54. As a Vendor Owner, I want a read-only list of my own Store's Customers showing name, phone, city and registration date, so that I know who shops with me.
55. As a Vendor Owner, I want to see only my Store's Customers, never another Store's, so that Stores stay private from each other.
56. As a Vendor Owner, I want no export of Customers in this phase, so that Customer data is not copied out unintentionally.
57. As a developer, I want the theme contract extended additively with the new pages and data and its version raised by a minor step, so that existing Themes keep working.
58. As a developer, I want the written Theme contract updated for the new pages and data, so that Theme authors can build them.
59. As a developer, I want the isolation helper to cover the new Store-owned data, so that the Phase 1 guarantee keeps holding.

## Implementation Decisions

- **Modules touched.** Auth owns the Customer account flows, address book, password broker and Customer resources for the panels. Storefront gains the controllers and page data for the new pages. Theming's contract gains the new pages. Tenancy and Core are unchanged apart from reuse. Dependency direction stays one-way; Auth does not reach upward into Storefront, which calls Auth's actions.
- **Existing foundation.** The Customer guard, Customer model scoped to its Store, per-Store uniqueness of email and mobile number, the registration/login/logout endpoints, the shared throttle, and the middleware that treats a Customer from another Store as signed out already exist from Phase 1. This phase extends them and puts pages in front of them; it does not replace them.
- **Responses.** The existing endpoints keep returning JSON to API-style requests. Requests from the Storefront pages receive redirects with validation errors and flash messages, so the same actions serve both.
- **Customer Identifier.** Email or mobile number, at least one required, each unique within its Store. Mobile numbers are normalised to one international format on write and on lookup, so every common Egyptian spelling reaches the same account. Login chooses the field by what was typed.
- **Password reset.** Email-based only, since OTP is out of scope. Reset tokens are stored with their Store so the same email in two Stores holds two independent tokens; lookup, issue, validate and delete are all Store-scoped. Tokens expire (60 minutes by default) and are single use. The request response is identical whether or not an account exists. A successful reset ends the Customer's other sessions. A Customer with only a mobile number cannot reset by email until they add an email; they can sign in and add one first. This is an open assumption.
- **Change password.** Requires the current password and ends other sessions.
- **Email verification.** A configuration flag, off by default. When on, an Customer who registered with an email receives a verification link and cannot complete login until verified, with a resend option; Customers registered with only a mobile number are not affected. Off by default means no behaviour change for Stores in this phase.
- **Rate limiting.** Login, registration, reset request and reset submission are limited per Store, per IP and, where there is one, per identifier. A rate-limited response states the wait time.
- **Deactivation.** A Customer has an active flag, true by default. Login is refused for an inactive Customer with a generic message. The existing Store-membership middleware also signs out any inactive Customer on their next request. Only Platform Admins can change the flag.
- **Addresses.** A Store-owned record per Customer: label, recipient name, mobile number, governorate, city, street address, notes, default flag. Governorates come from a fixed list of Egypt's governorates kept in configuration and exposed as an enum-like set. At most one default per Customer, enforced when writing, not only in the form; the first address is default automatically; deleting the default promotes the most recently added remaining address; a Customer may keep at most ten addresses. Create, update, delete and set-default are Actions shared by every caller, with a Policy allowing a Customer only their own addresses. Lookup of another Customer's address returns not-found, not forbidden.
- **Intended address.** A guest sent to Login keeps the page they came from and returns there after login or registration. Only a path inside the same Store is accepted; anything else is ignored and the Customer lands on their account page. Checkout does not exist yet, so the behaviour is proven on the account and address pages.
- **Theme contract.** Adds six pages (Login, Register, ForgotPassword, ResetPassword, Addresses, and an extended Account) and raises the contract version by a minor step. The new pages are optional for Themes: a Theme that lacks one uses the default Theme's page, which keeps the change additive. The shell data gains Customer links (login, register, logout, account, addresses) and the signed-in state needed for the header. Account gains profile and password data; Addresses gains the address list, the governorate list and limits. The default Theme implements all of them, right-to-left, mobile first, accessible, with Arabic messages. The written contract is updated in the same change.
- **Admin Panel.** A read-only Customer resource across Stores, using the sanctioned cross-Store helper only inside Admin code: Store filter, search by name, email and mobile, a view page with addresses, and Activate/Deactivate actions. No create, edit or delete.
- **Vendor Panel.** A read-only Customer resource for the Vendor Owner's own Store: name, phone, city (from the default address) and registration date; view page; no export, no email shown.
- **Language.** Validation, reset and verification messages are Arabic (with English counterparts) in the language files.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: given this request, panel interaction or actor, what response, redirect, email, stored data or page props result. Do not assert internal classes, token table layout or cache keys.
- **Seam 1: the HTTP and panel boundary (the only seam).** Feature tests through the Storefront routes, the Admin Panel and the Vendor Panel. Cases:
  - registration with email only, mobile only, both, neither; per-Store uniqueness; the same identifiers in two Stores being independent accounts with independent passwords; mobile number formats reaching the same account;
  - login by email and by mobile, generic failure, throttling for login, registration and reset, deactivated Customer refused, deactivated session ended on the next request, logout;
  - a Customer's session unusable in another Store while still valid in their own;
  - password reset: generic response, single-use and expiring tokens, a token from Store A refused in Store B, parallel pending resets for the same email in two Stores, other sessions ended after reset, mobile-only Customer cannot request by email until an email is added;
  - profile edit keeping uniqueness and at least one identifier; password change requiring the current password and ending other sessions;
  - email verification off by default and, when switched on, blocking login until verified and resending;
  - addresses: validation including governorate list, first-is-default, single default, delete promotes, ten-address limit, a Customer cannot see, edit, delete or set default on another Customer's address (returns not-found), including across Stores;
  - intended address accepted only for same-Store paths, with crafted external and cross-Store targets ignored;
  - every new page renders through the Theme with the default Theme's fallback when a Theme lacks it, and the page data carries the raised contract version;
  - Admin: Store filter, search, view with addresses, activate/deactivate, no edit; Vendor: only own Store's Customers, only the allowed columns, no export, and a Platform Admin or Customer cannot open the Vendor list.
- **Isolation.** Reuse the Phase 1 isolation helper for the address book and the new Store-owned data, and keep the existing cross-Store session tests.
- **Front-end gate.** The type check and production build must pass.
- **Framework and gate.** PHPUnit, no new test dependency; formatting and tests must pass.
- **Prior art.** The Phase 1 Customer authentication and Store-scope tests, the Phase 2 Vendor onboarding tests, and the Phase 3–4 panel and Storefront page tests are the templates.

## Out of Scope

- Social login and one-time-password (OTP) login or reset (explicitly out of scope in the phase plan).
- Orders, order history content, carts, checkout itself (Phases 6–7); the Orders page stays a placeholder.
- Guest checkout, saved payment methods, wishlists and reviews.
- Customer data export, account self-deletion and data-portability tools.
- Editing or deleting Customers from either panel, and Vendor-side messaging to Customers.
- Mobile-number ownership verification.
- Two-factor authentication and security headers (Phase 12).

## Further Notes

- **Tracker.** As in earlier phases, no tracker is configured and the repo has no git remote, so this spec lives in `docs/specs/` and tickets go to `.scratch/phase-5-customer-accounts/issues/` with `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real tracker.
- **Seams to confirm.** One seam only: the HTTP and panel boundary, reusing the existing isolation helper. There is no pure-unit seam because the logic worth testing (normalising a mobile number, the default-address rule) is reached through those requests. Say so if you want a unit seam for phone normalisation.
- **Suggested ticket order.** Mobile-number normalisation and the active flag; account pages for Login and Register through the Theme; logout and header state; Store-scoped password broker with Forgot and Reset pages; profile and change password; optional email verification; address book with Actions and Policy; Addresses page; intended-address handling; Admin Customer resource; Vendor Customer resource; contract bump and document; phase gate.
- **Open assumptions.**
  - A mobile-only Customer cannot self-serve a reset until they add an email.
  - Email verification, when switched on, blocks login until verified; it is off by default.
  - The new Theme pages are optional so the contract change is a minor, additive one.
  - Ten addresses per Customer.
  - The Vendor Owner does not see Customer emails.
  - Reset links last 60 minutes.
