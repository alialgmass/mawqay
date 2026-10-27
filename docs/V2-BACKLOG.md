# V2 backlog

Ideas and gaps found during the MVP, kept out of scope so the pilot stays small. Nothing here is committed.

## From the plan

- Online payment through the `PaymentGateway` contract.
- Courier gateway through the `ShippingProvider` contract with automatic tracking.
- Custom domains or subdomains by adding a Store resolver.
- Vendor staff and permissions; Vendor self-registration.
- Public product reviews, coupons, a page builder for Themes.
- WhatsApp or SMS delivery using the `NotificationChannel` contract (survey invitations for Customers without email are the first use).

## Found while hardening (Phase 12)

- **Report date basis.** Revenue, top products and rating figures use the Order's creation date. If reports should follow the payment date, record a paid-at time on the Order first.
- **Backups and error tracking.** A backup package, an error tracker and a queue dashboard were not added (dependency decisions). `DEPLOYMENT.md` documents a scripted backup and log-based monitoring until they are approved.
- **Two extra Themes** (`classic`, `minimal`) were not built in Phase 12; the Theme contract and the default Theme are ready for them.
- **Accessibility and right-to-left pass** with an automated checker, and a manual pass at phone, tablet and desktop widths, is still to be done.
- **SEO.** Per-Store and per-product meta tags, share images, canonical addresses and a server-rendering decision are still to be done.
- **Custom admin and Vendor panel look** aligned with the brand tokens.
- **Second factor for Vendor Owners** (optional now) could become required.
- **Content security policy for the panels** starts in report-only mode; tighten it once the report shows what the panels really load.
- **Payout proof preview** uses a short-lived address; confirm it works on the production disk, or add an authorised download route.
- **Single browser journey test** (onboarding to a reconciled payout) with the repo's end-to-end runner.
- **Isolation suite**: the enumeration checks structure (every Store-owned table has a scoped model, every resource has a Policy); add per-model cross-Store read and write cases as models are touched.
