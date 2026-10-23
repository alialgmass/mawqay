# THOB — Domain Context

THOB (ثوب) is a B2B textile/fabric marketplace for the Egyptian market. This backend
is a **Laravel modular monolith** exposing a versioned REST API (`/api/v1/`) consumed by
a separate mobile marketplace client, plus a **Filament v5 admin panel** (`/admin`).

This file is the canonical glossary. It defines what each term **means**, not how it is
implemented. When code and this file disagree, fix one of them — do not let the drift stand.
Related decisions live in `docs/adr/`.

---

## Actors & accounts

**User** — <span dir="rtl">مستخدم</span>
A single authenticated identity, keyed by a verified Egyptian phone number (one account
per phone, `BR-ACC-01`). Has exactly one **Account Type** and a `status`
(`pending_type_selection` → `active` → `suspended`).
_Avoid_: "member", "customer" (Customer is one specific account type).

**Account Type** — <span dir="rtl">نوع الحساب</span>
One of `importer`, `wholesaler`, `retailer`, `customer`. Chosen once after phone
verification and never self-changed (`BR-ACC-02`, admin-mediated change is R2). Determines
the entire role experience. Importer/Wholesaler/Retailer require a **Business Account**;
Customer never does.
_Avoid_: "role" (roles in the Spatie sense are only `admin`).

**Business Account** — <span dir="rtl">حساب تجاري / ملف الشركة</span>
The company profile attached to a business-type User: company name, activity, governorate,
address, contact person, contact channels. One per User. Starts `unverified`. Carries the
**Verified** flag and owns the catalog, subscription, inquiries and leads.
_Avoid_: "business profile", "company", "supplier account", "vendor" — all mean this one thing.

**Supplier** — <span dir="rtl">مورّد</span>
A Business Account **as seen by a buyer** in search and on a product page. Not a separate
model — it is the buyer-facing projection of a Business Account (`SupplierCardResource`).
_Avoid_: "seller" when talking about discovery; use Seller for the transactional role.

**Buyer** — <span dir="rtl">مشتري</span>
The party that sends an inquiry or RFQ. In R1 this is a `wholesaler` (and `retailer`,
who is dual-role). Not a stored attribute — it is a **relationship role** on an Inquiry.

**Seller** — <span dir="rtl">بائع</span>
The Business Account that receives an inquiry and can be quoted from. The `importer`
(and dual-role `retailer`). A relationship role on an Inquiry, not a stored attribute.

**Admin** — <span dir="rtl">مسؤول</span>
Holds the Spatie `admin` role. Operates only through the Filament panel and the
`/api/v1/admin/*` endpoints. Every admin action is written to the **Audit Log**.

---

## Verification

**Verification Request** — <span dir="rtl">طلب توثيق</span>
One review cycle for a Business Account: `pending` → `approved` | `rejected`. Created
implicitly when the owner uploads the first document; becomes reviewable once **submitted**
(`submitted_at` set). A rejection lets the owner re-upload, which opens a new request.

**Verification Document** — <span dir="rtl">مستند التوثيق</span>
A file (commercial register, tax card, …) attached to a Verification Request. Stored on
the **private** `verification` disk, never a guessable public URL. Downloaded only through
a short-lived signed URL, gated by policy (owner or admin).

**Verified** — <span dir="rtl">موثّق</span>
A boolean derived from `business_accounts.verification_status === verified`. Surfaces as
the "Verified" badge on every supplier/product card and detail resource. Disappears
immediately if verification is revoked (suspension). Being unverified does **not** hide a
supplier's products from search today (Open Decision #5).

**Document Type** — <span dir="rtl">نوع المستند</span>
An admin-editable taxonomy row (`document_types`) with an `is_required` flag. Lets the
mandatory-document list change without a deploy.

---

## Subscriptions & entitlements

**Subscription Plan** — <span dir="rtl">خطة اشتراك</span>
A named tier bound to one Account Type (`Importer Basic/Pro/Premium`, `Wholesaler`,
`Retailer`). Carries a set of **Entitlements**. `price` is nullable — pricing is an open
product decision, never hardcoded.

**Entitlement** — <span dir="rtl">صلاحية</span>
A single `key → value` capability on a plan: `product_limit`, `inquiry_limit`,
`search_priority`, `featured_products`, `featured_supplier`, `featured_placement`,
`analytics_depth`, `support_level`, `contact_info_visible`. Value is a string:
`"true"`/`"false"` for booleans,
a number for limits, a free string otherwise. Admin-editable without a deploy (`MNT-NFR-02`).
_Avoid_: "feature flag" (those are the R4 `feature()` gates), "permission".

**Subscription** — <span dir="rtl">اشتراك</span>
The link between a Business Account and a Plan, with `status`
(`active`/`expired`/`cancelled`/`restricted`), `current_period_end`, and optional
`trial_ends_at`. Exactly one active Subscription per Business Account at a time.

**EntitlementService** — <span dir="rtl">بوابة الصلاحيات</span>
The **only** server-side gate for plan capabilities (`BR-SUB-01`, `SEC-NFR-04`). Resolves
`can(key)` / `get(key)` / usage counters against the DB — never against a client-sent plan
name. Any client-supplied plan or entitlement claim is untrusted.

**Usage Counter** — <span dir="rtl">عدّاد الاستخدام</span>
A `key → current_value` row per Business Account (`product_count`, `inquiry_count`)
compared against the matching limit entitlement.

**Restricted state** — <span dir="rtl">حالة مقيّدة</span>
What a Subscription becomes on expiry: products are **hidden, never deleted** (`BR-SUB-03`);
the seller is prompted to renew.

**Upgrade / Downgrade / Cancel** — <span dir="rtl">ترقية / تخفيض / إلغاء</span>
Upgrade applies immediately (new Subscription row, old one cancelled). Downgrade and Cancel
are deferred: they record intent in `subscriptions.notes` and take effect at
`current_period_end` via the `subscriptions:process-period-ends` command (`BR-SUB-02`) —
the paid period is never truncated.

---

## Discovery

**Search** — <span dir="rtl">بحث</span>
Public, pre-sign-in product/supplier discovery. Arabic + English, tolerant of common
fabric-name spelling variants via the **Search Normalizer** (fold alef/hamza, strip
tashkeel, map synonyms). MySQL `FULLTEXT` in production, `LIKE` on SQLite.

**Search Normalizer** — <span dir="rtl">مُطبِّع البحث</span>
Pure function that reduces a query or a product name to a comparable canonical form
(`SearchNormalizer::normalize`). Keeps `products.search_text` / `business_accounts.search_text`
in sync via observers.

**Featured** — <span dir="rtl">مميّز</span>
A within-page ranking boost (max `FeaturedRanker::BOOST_POSITIONS = 12`) for products or
suppliers whose plan grants `featured_products` / `featured_supplier`. Always **visually
labeled** (`featured: true` in the resource) — never a silent manipulation (`BR-SRC-01`).

**Buyer-visible** — <span dir="rtl">ظاهر للمشتري</span>
The `Product::scopeBuyerVisible` rule: `published`, not hidden/unavailable/deleted, owner
not suspended (`BR-SRC-02`), and the owner's subscription not lapsed (`BR-SUB-03` — a
business that never subscribed keeps current visibility; one whose plan expired has its
products hidden until renewal, never deleted). A non-visible product returns **404**, never
403, from the public detail endpoint.

**Zero-result search** — <span dir="rtl">بحث بلا نتائج</span>
A search that returns exactly zero rows. Logged to `search_logs` (term + count + optional
user id) as an unmet-demand signal for the admin liquidity dashboard (`US-SRC-11`).
A logging failure never breaks the response.

**Favorite** — <span dir="rtl">مفضّلة</span>
A polymorphic save of a Product or a Supplier by a User. Unique per
`(user, favoritable_type, favoritable_id)` — favoriting twice is idempotent (`BR-FAV-01`),
not an error. Removed only by its owner (`DELETE /favorites/{id}`).

**Comparison** — <span dir="rtl">مقارنة</span>
An on-demand side-by-side view of **up to 4** Products or Suppliers (`BR-CMP-01`). Not
persisted — the client passes the ids (`GET /compare?type=&ids=1,2,3,4`). A 5th id is
rejected with a clear message; unknown/non-visible ids come back in `missing_ids`.

---

## Inquiries, RFQs, quotations, leads

**Inquiry** — <span dir="rtl">استفسار</span>
A buyer-initiated contact with a seller, optionally about a specific product
(`buyer_id`, `seller_business_id`, `product_id?`, `message`). The entry point of the
sales conversation. Every Inquiry is also a **Lead** — there is no separate leads table
(`BR-INQ-01`).

**Lead** — <span dir="rtl">عميل محتمل</span>
An Inquiry viewed from the seller's pipeline, tracked by `inquiries.lead_status`:
`new` → `in_progress` → `done` | `not_completed` (exactly these four). Only the seller
changes it. The "Lead Management screen" is `GET /inquiries?role=seller`.
_Avoid_: treating Lead as a distinct entity — it is a facet of Inquiry.

**RFQ** (Request for Quotation) — <span dir="rtl">طلب عرض سعر</span>
A structured price request hung off an Inquiry: `product_id`, `quantity`, `color_id?`,
`needed_by_date`. The product must belong to the Inquiry's seller. A quantity below the
product's **MOQ** sets `below_moq: true` — a warning, not a block. An RFQ re-checks the
seller's `inquiry_limit` but does **not** consume a second inquiry.

**MOQ** (Minimum Order Quantity) — <span dir="rtl">الحد الأدنى للطلب</span>
A per-product threshold (`products.moq`, nullable). Below-MOQ RFQs are flagged, allowed.

**Quotation** — <span dir="rtl">عرض سعر</span>
A seller's reply to an RFQ: `price`, `availability_note?`, `valid_until`. Only the RFQ's
addressed seller may create one. After `valid_until` passes it renders as
`expired: true` and is not actionable as current pricing.

**Report** — <span dir="rtl">بلاغ</span>
A durable abuse flag on an Inquiry raised by either party (`reportable_type`,
`reportable_id`, `reporter_id`, `reason`). Record only — the admin dispute/moderation
workflow is Phase 9.

---

## API conventions

**Envelope** — <span dir="rtl">الغلاف الموحّد</span>
Every `/api/v1/` response is `{ custom_code, status, message, body, info }`. `status` is
`true` for any 2xx. `body` carries the payload (or, on validation error, a
`field → [messages]` map). See `docs/API_REFERENCE.md` for the `custom_code` registry.
Known deviations (403/404 and the two Subscriptions write requests fall back to Laravel's
native shape) are documented there.

**Handoff Token** — <span dir="rtl">توكن التسليم</span>
The encrypted, short-lived (`handoff_ttl_seconds`, default 600) `registration_token` /
`reset_token` returned by `POST /auth/otp/verify` and spent by `register` / `password/reset`.
Distinct from the Sanctum bearer **token** returned after registration/login.

**next_onboarding_step** — <span dir="rtl">الخطوة التالية</span>
A hint in auth responses: `account_type_selection` → `business_profile` → `none`.

**Quote request** — <span dir="rtl">طلب عرض سعر</span>
The buyer's one-step "ask for a price" on a product: an Inquiry, an RFQ (quantity, colour, needed-by
date, notes) and the Conversation that carries the discussion, created together. The conversation opens
with a structured summary message from the buyer.

**Plan limit** — <span dir="rtl">حد الباقة</span>
A numeric entitlement a buyer's plan caps (saved suppliers, monthly quote requests, active
conversations, supplier-comparison size). Non-numeric values such as `unlimited`, an absent key, or no
subscription never block. Distinct from the inquiry limit, which also protects the *seller*.

**Static page** — <span dir="rtl">صفحة ثابتة</span>
Admin-edited bilingual copy for About, Privacy Policy and Terms, served by the API so the apps do not
hard-code legal text.

**Presence** — <span dir="rtl">الحضور</span>
Whether a conversation's other party is online, read from the recency of their API token use. Shown
only to the participants of that conversation.

---

## Customer shopping

**Cart** — <span dir="rtl">السلة</span>
A customer's server-side list of products and fractional quantities. Guests keep theirs on the device and merge it
in after login. Priced at the product's current price; flags a price change or an unavailable item.

**Checkout** — <span dir="rtl">إتمام الطلب</span>
One purchase of a cart: it groups the per-shop Orders it produced under a single `reference` (the order / shipment
number the shopper sees) and snapshots the delivery address, recipient, payment method and totals.

**Delivery Address** — <span dir="rtl">عنوان التوصيل</span>
A shopper's saved address with a client-supplied map pin; exactly one is the default.

**Payment Method** — <span dir="rtl">طريقة الدفع</span>
`card`, `vodafone_cash`, `orange_cash` or `fawry`. Money only moves when online payment is released; card data never
reaches THOB — only a provider token, brand, last four digits and expiry are kept.

**Shop Category** — <span dir="rtl">فئة المتجر</span>
A discover filter over shops: `discounts` (has a discounted product), `trader`, `factory` (has a factory licence).

---

## Design alignment (Oct 2026)

**Rating** — <span dir="rtl">تقييم</span>
A buyer's 1–5 score (with an optional comment) of a Supplier, one per buyer and supplier, editable. Only a buyer with a
**Completed** order from that supplier may rate it, never one's own business. The supplier's cached `rating` / `ratings_count`
count only visible ratings; an admin can **hide** an abusive one (audited), and a hidden rating stays hidden when its author
edits it.

**Quotation decision** — <span dir="rtl">قرار العرض</span>
The buyer's final answer to a Quotation: **accepted** (creates an Order for the RFQ quantity at the quoted price) or
**rejected**. An expired or already-answered quotation is a 409. Concurrent answers cannot both win.

**Direct conversation** — <span dir="rtl">محادثة مباشرة</span>
A chat with a Supplier started without a product. It is still an Inquiry (no product) with its Conversation, under the same
inquiry and active-chats limits as any new inquiry; idempotent per buyer and supplier.

**Market alert trigger** — <span dir="rtl">شرط التنبيه</span>
What an alert waits for: `new_product`, `price_below` (a product at or under `price_max`, also when a live product's price
drops to it) or `new_supplier` (the first product of a supplier in a governorate). Optionally scoped to one supplier.

**Display preferences** — <span dir="rtl">تفضيلات العرض</span>
A user's currency, unit system and numeral style. The API stays canonical (EGP, metre, kilogram); clients convert with the
published `rate_to_egp` and unit factors, which an admin edits under Settings → Currency rates.

**Invoice status / payment** — <span dir="rtl">حالة الفاتورة</span>
A paid plan's invoice starts `pending` and becomes `paid` when settled. Only the subscription's owner pays it, by card token,
mobile wallet (Vodafone / Orange / Etisalat Cash) or Fawry; with online payment off the method is only recorded.

**New arrivals** — <span dir="rtl">وصل حديثاً</span>
Products published within `catalog.new_badge_days` (default 7). `filters[new_only]` lists them; `filters[from_saved_suppliers]`
narrows the list to suppliers the caller saved.

**Expected delivery date** — <span dir="rtl">موعد التسليم المتوقع</span>
Set by the seller on a business order when confirming or moving it along; a shop order falls back to its checkout's estimate.
