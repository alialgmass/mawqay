# خطة التنفيذ: 12 فيز + Prompt لكل فيز

منصة Multi-Tenant لتجارة أزياء المحجبات (Laravel + Vue + Filament). كل فيز بيخلّص **Backend + Frontend + Admin Panel (Filament)** مع بعض، وبيطلع شغّال وقابل للاختبار قبل ما تنتقل للي بعده.

## قرار: لوحتا الأدمن والتاجر بـ Filament

- **Problem:** لوحتا الأدمن والتاجر فيهم جداول وفورمز وفلاتر كتير، وبناؤهم بـ Vue من الصفر بياكل أسابيع.
- **Options:** (A) Filament للوحتين، والمتجر بـ Inertia + Vue. (B) Inertia + Vue للكل. (C) Filament للأدمن فقط.
- **Trade-offs:** (A) أسرع بكتير في الـ CRUD، ونفس لغة Laravel، لكن لوحة التاجر تبقى بشكل Filament وتخصيصها محدود. (B) تحكم كامل لكن أضعاف الشغل. (C) بيكرر الشغل مرتين.
- **Decision:** (A): `/admin` و `/vendor` بـ Filament، و `/{store-slug}` بـ Inertia + Vue (الثيمات).
- **Why:** التاجر والأدمن محتاجين وظائف مش شكل مخصص، والثيمات هي اللي لازم تتصمم بحرية.

**تأثير على الـ PRD:** القرار D2 (القسم 4) و `Backoffice` (القسم 5) والقسم 14 (Design System) يتعدلوا: shadcn-vue للـ Storefront فقط، ولوحات Filament تتخصص بـ Custom theme بنفس ألوان المنصة. الكود في الملف ده هو المرجع.

## طريقة الاستخدام

1. افتح جلسة جديدة مع الـ AI (Claude Code أو غيره) لكل فيز.
2. الصق **MASTER CONTEXT** أولًا، ثم الصق **Prompt الفيز**.
3. راجع الناتج مقابل **Definition of Done** قبل ما تقفل الفيز.
4. الـ Prompts بالإنجليزي لأن أدوات الكود بتشتغل أدق بيه؛ الشرح حواليها بالعربي.

## الفيزات في سطور

| # | الفيز | أهم الناتج | المدة المقدرة |
| --- | --- | --- | --- |
| 1 | الأساس (Foundation) | Modules، Tenancy، Guards، Filament Panels، CI | 1.5 أسبوع |
| 2 | المتاجر والهوية | إنشاء تاجر ومتجر، الدعوة، الهوية، حالات المتجر | 1 أسبوع |
| 3 | الكتالوج | تصنيفات، منتجات، Variants، صور، صفحات المنتجات | 2 أسبوع |
| 4 | محرك الثيمات | Theme contract، الثيم الافتراضي، اختيار الثيم وإعداداته | 2 أسبوع |
| 5 | حسابات العملاء | تسجيل ودخول لكل متجر، العناوين | 1 أسبوع |
| 6 | الكارت والـ Checkout والطلبات | Cart، حجز مخزون، إنشاء الطلب، آلة الحالات | 2 أسبوع |
| 7 | الدفع اليدوي | رفع الإيصال، قائمة مراجعة الأدمن، قبول ورفض | 1.5 أسبوع |
| 8 | الـ Ledger والعمولة | قيود الدفتر، أرصدة التجار، Reconciliation | 1.5 أسبوع |
| 9 | الشحن والتسليم والاسترجاع | شحنة يدوية، تتبع، Delivered، Refund | 1.5 أسبوع |
| 10 | الصرف والتقارير | طلبات الصرف، دفع التاجر، Dashboards وتقارير | 1.5 أسبوع |
| 11 | الرضا والإشعارات | استبيان الرضا، تنبيه التقييم المنخفض، قوالب الإشعارات | 1 أسبوع |
| 12 | التثبيت والإطلاق | ثيمان إضافيان، أمان، أداء، E2E، Pilot | 2 أسبوع |

المدة المجمّعة قرابة 19 أسبوع، أطول من تقدير الـ PRD (13.5 أسبوع) لأن كل فيز هنا يشمل الواجهة واللوحتين كاملين مع الاختبارات؛ عدّل الأرقام بعد الفيز 1.

---

## MASTER CONTEXT (الصقه في بداية كل جلسة)

```text
You are a senior Laravel engineer building a multi-tenant e-commerce platform for modest-fashion (hijab) vendors in Egypt. Treat everything below as fixed requirements in every phase.

PRODUCT
- Each vendor ("store") gets its own storefront with its own logo, identity and selectable theme, under a PATH PREFIX: /{store-slug}. No subdomains in the MVP.
- The PLATFORM collects payments manually: the customer transfers via Vodafone Cash or InstaPay to the platform's account, uploads a receipt, and a platform admin approves or rejects it in the admin panel.
- The platform takes a commission on each sale; the remainder is owed to the vendor (held by the platform) and paid out manually later.
- Currency: EGP, stored as integer piasters (never floats). UI: Arabic, RTL first, mobile first.
- One cart, one order and one active payment belong to exactly one store. Shipping fees are set and kept entirely by the vendor; commission applies to product lines only (default 10%, per-store override, no per-transaction minimum). Vendors are created by invitation from a platform admin only.
- Order journey: customer registers/logs in per store -> cart -> checkout -> pays manually + uploads receipt -> admin approves -> vendor ships manually (courier name + tracking number) -> delivered -> satisfaction survey.

STACK
- Laravel (latest stable), PHP 8.3+, MySQL, Redis (cache + queues), Pest, Laravel Pint, Larastan.
- Modular code with nwidart/laravel-modules (Modules/ directory). Follow the directory structure of the installed version.
- Storefront: Inertia + Vue 3 + Tailwind, themeable. Routes under /{store:slug}.
- Admin Panel at /admin and Vendor Panel at /vendor: BOTH are Filament (latest stable). Do not build these panels with Inertia/Vue.
- Each module exposes its Filament resources through a Filament Plugin class (e.g. CatalogPlugin) registered in the admin and/or vendor panel providers, so panels stay modular.

TENANCY
- Single database; every store-owned table has store_id.
- Trait BelongsToStore + global scope StoreScope on every store-owned model; store_id is set automatically on create.
- Storefront: middleware IdentifyStore resolves the store from the {store:slug} route parameter through a swappable StoreResolver interface (PathStoreResolver now; domain resolvers later) and exposes Store::current().
- Vendor Panel: Filament multi-tenancy with Store as tenant (slug attribute), plus the BelongsToStore scope as a second layer. Admin Panel sees all stores; disabling the scope is allowed ONLY inside admin-panel code through one explicit helper.
- Reserved slugs: admin, vendor, api, login, register, logout, storage, assets, build, up, livewire, filament (config-driven list).
- Queue jobs carry store_id explicitly. Cache keys use the prefix store:{id}:. Files live under stores/{id}/.

ARCHITECTURE RULES
- Module dependencies go one way: Core <- Tenancy <- (Auth, Catalog, Theming) <- Sales <- (Payments, Shipping, Feedback) <- Ledger; Storefront and Notifications consume the others. Cross-module communication uses Events and Contracts (interfaces), never direct Eloquent relations upward.
- Thin controllers; business logic in Action/Service classes; Form Requests for validation; Policies for authorization; DB transactions for anything that touches money, stock or order status.
- Money: integer piasters, a Money value object in Core. Ledger entries are append-only (no update/delete). Commission rate and amount are snapshotted on each order_item at order creation.
- Order status machine (all transitions logged in order_status_logs): pending_payment -> payment_review -> paid -> processing -> shipped -> delivered -> completed; also cancelled and refunded.
- i18n: Arabic default, all user-facing strings in lang files.
- Tests: Pest per module (inside the module). EVERY phase must add tenant-isolation tests proving store A cannot read or modify store B data.

WORKING AGREEMENT
- Start with a short plan (files, tables, routes), then implement. Do not add features outside the current phase.
- If something is ambiguous, pick the most reasonable option, state the assumption in the final summary, and continue. Ask only if truly blocked.
- Before finishing run: php artisan test, Pint, Larastan. Everything must pass.
- Final message: files created/changed, migrations, commands to run (seed, queue, schedule), assumptions, and known follow-ups.
```

---

## الفيز 1: الأساس (Foundation)

**الهدف:** مشروع Laravel بهيكل Modules، Tenancy شغال، ثلاثة Guards، ولوحتا Filament فاضيتين تسجلوا دخول، مع CI واختبارات عزل.

**Definition of Done**

- [ ] `/admin` يدخله الأدمن فقط، و `/vendor` يدخله مالك متجر فقط، ومالك متجر A لا يدخل متجر B.
- [ ] `/{slug}` بيحمّل المتجر الصحيح، وslug غير موجود أو محجوز يعطي 404.
- [ ] اختبارات العزل والـ CI تعدي (Pest + Pint + Larastan).

```text
[Paste MASTER CONTEXT first]

PHASE 1 - FOUNDATION

Goal: a working skeleton: modules, tenancy, guards, Filament panels, storefront shell, CI.

BACKEND
1. Create a fresh Laravel app (latest stable). Install nwidart/laravel-modules. FIRST verify it is compatible with the installed Laravel version; if not, tell me and use the closest supported setup before continuing.
2. Create modules: Core, Tenancy, Auth, Storefront, Backoffice (Backoffice only holds panel providers/config for Filament).
3. Core: BelongsToStore trait + StoreScope (auto-filter by Store::current() or the Filament tenant; auto-set store_id on create), Money value object (integer piasters, EGP formatting helper), an explicit helper to run code without the store scope (admin only), base test helpers.
4. Tenancy: stores migration (id, slug unique, name, status [draft|active|suspended], theme_id nullable, theme_settings json nullable, logo_path nullable, commission_rate nullable decimal, contact fields, timestamps), Store model, StoreResolver interface + PathStoreResolver, IdentifyStore middleware, Store::current(), reserved-slugs config + validation rule (3-30 chars, a-z 0-9 hyphen, not reserved, unique).
5. Auth: users table additions (role enum: admin, vendor_owner; store_id nullable), customers table (store_id, name, email, phone, password, unique store_id+email) and a `customer` guard (no UI yet). Policies/gates skeleton.
6. Seeders: one platform admin, one demo store + vendor owner (local only).

FRONTEND (storefront shell)
7. Install Inertia + Vue 3 + Tailwind with Vite. Make Vite load module assets (merge module statuses as documented by nwidart).
8. Base layout with dir="rtl", lang="ar", logical-property utilities, a CSS-variables token file (--color-primary, --color-secondary, --color-bg, --color-surface, --color-text, --color-text-muted, --color-border, --radius-md, --font-body, --font-heading) with sensible defaults.
9. Route group /{store:slug} with a placeholder Home page showing the store name; 404 page for unknown stores.

ADMIN & VENDOR PANELS (Filament)
10. Install Filament. Create AdminPanel (/admin, only role=admin via canAccessPanel) and VendorPanel (/vendor, only role=vendor_owner, Filament multi-tenancy with Store as tenant using slug; a user belongs to exactly one store, hide the tenant switcher).
11. Establish the module-plugin pattern: each module may provide a Filament Plugin registered in the panel providers. Add a StoreResource skeleton in Tenancy for the admin panel (list + view only) to prove the pattern.
12. Arabic locale and RTL enabled in both panels.

TESTS & CI
13. Pest tests: slug rules, reserved slugs, IdentifyStore resolution, BelongsToStore scoping and auto store_id, panel access rules, and a reusable isolation-test helper/trait that later phases will use.
14. GitHub Actions workflow: composer install, npm build, Pint (check), Larastan, Pest.

OUT OF SCOPE: store creation UI, branding, catalog, themes.

DELIVER: summary + how to run (migrate, seed, npm run dev, queue).
```

---

## الفيز 2: المتاجر والهوية

**الهدف:** الأدمن ينشئ تاجر ومتجر ويبعت دعوة، والتاجر يضبط هوية متجره، والمتجر العام يعكس الهوية وحالته.

**Definition of Done**

- [ ] الأدمن ينشئ تاجر + متجر من `/admin` فيصل إيميل دعوة بكلمة سر.
- [ ] التاجر يغيّر اللوجو والألوان من `/vendor` ويظهر فورًا على `/{slug}`.
- [ ] متجر Draft يعطي 404 للزوار، و Suspended يعرض صفحة صيانة.

```text
[Paste MASTER CONTEXT first]

PHASE 2 - STORES & IDENTITY (prerequisite: Phase 1 done)

Goal: onboarding of vendors by the admin and store identity management by the vendor.

BACKEND
1. Platform settings: a platform_settings table (key, value json) and a typed Settings service in Core (cached). Seed defaults: default_commission_rate=10 (percent), stock_reservation_hours=24, return_window_days=7, survey_delay_days=2, payment_review_sla_hours=4, payment_review_overdue_hours=72, payment_retry_window_hours=6, min_payout_amount=50000 (piasters = 500 EGP).
2. Tenancy: action CreateStoreWithOwner (transaction): creates the store (status draft) + vendor_owner user + an invitation (signed URL, expires in 72h) that lets the owner set a password. Mail notification in Arabic. Action ResendInvitation.
3. Store identity columns/settings: primary_color, secondary_color, favicon_path, contact email/phone/WhatsApp, social links (json), footer_text, shipping_origin (text). Validate colors as hex.
4. Store status flow: draft -> active -> suspended (and back). Middleware EnsureStoreIsPublic: draft => 404 for guests (owner can preview), suspended => maintenance page with HTTP 503, no cart additions, no checkout and no payout requests; in-flight orders continue (receipt review, shipping) and funds keep maturing; payouts resume on reactivation.
5. Slug redirects: table store_slug_redirects; changing a slug (admin only) keeps the old one redirecting 301.
6. A helper that checks color contrast (WCAG ratio) and picks black/white text for a given primary color.

FRONTEND
7. Storefront shell uses the store's logo, name, colors (injected as CSS variables), favicon, footer text and contact links. Add maintenance page and store-not-found page (RTL, mobile first).

ADMIN PANEL (Filament /admin)
8. StoreResource full CRUD: create form runs CreateStoreWithOwner (store name, slug with live validation, owner name/email/phone, commission override optional), table with status badge, filters (status), actions: Activate, Suspend, Resend invitation, Change slug (with redirect). Show owner via relation manager.
9. A Platform Settings page editing the settings from step 1 (permission: admin only).

VENDOR PANEL (Filament /vendor)
10. Accept-invitation page (set password) then redirect to the panel.
11. Settings page "Store identity": logo (stored under stores/{id}/), favicon, name, primary/secondary color pickers with contrast warning, contact info, social links, footer text. Vendor cannot edit slug, status or commission.
12. Dashboard placeholder showing store status and a link to the public store.

TESTS
13. Invitation expiry and reuse, slug validation and redirect, status gating (draft/suspended), vendor cannot edit another store, vendor cannot change commission/status, isolation tests, color contrast helper.

OUT OF SCOPE: products, themes selection (only colors), customers.
```

---

## الفيز 3: الكتالوج (Catalog)

**الهدف:** التاجر يدير التصنيفات والمنتجات والـ Variants والصور، والعميلة تتصفحها على المتجر.

**Definition of Done**

- [ ] التاجر يضيف منتج بمقاسات وألوان وصور من `/vendor` ويظهر على `/{slug}` بعد النشر.
- [ ] منتجات تاجر لا تظهر عند تاجر آخر لا في المتجر ولا في اللوحة.
- [ ] الأدمن يشوف كل المنتجات ويقدر يخفي منتج مخالف.

```text
[Paste MASTER CONTEXT first]

PHASE 3 - CATALOG (prerequisite: Phases 1-2)

Goal: full catalog management for vendors and browsing for customers.

BACKEND (module Catalog)
1. Migrations/models (all with store_id + BelongsToStore): categories (2-level tree: parent_id, name, slug, sort, is_active), products (category_id, name, slug, description, base_price, sale_price nullable, status draft|published|hidden_by_admin, sort), product_variants (product_id, sku, size, color, price_override nullable, stock, reserved default 0), product_media (product_id, path, sort). Slugs unique per store. Max 8 images per product.
2. Image pipeline: on upload, queue a job that creates WebP in 3 sizes (thumb, medium, large) under stores/{id}/products/; keep original.
3. StockService with row-level locking inside transactions: available = stock - reserved; methods reserve(variant, qty), release(variant, qty), commit(variant, qty). It will be used by Phase 6; build and unit test it now, including a concurrency test.
4. Query scopes: published(), inStock(). Price accessors return Money.
5. Simple name search (LIKE; keep behind a SearchService interface so it can be swapped later).

FRONTEND (Storefront module, default look only; the theme engine comes in Phase 4)
6. Controllers + Inertia pages: Home (latest published products + top-level categories), Category listing (pagination, sort by newest/price), Product page (gallery, price, sale price, variant picker for size/color with disabled out-of-stock options), Search results. Components: ProductCard, VariantPicker, Money, Breadcrumbs, EmptyState. Mobile first, RTL.
7. SEO basics per page: title, meta description, Open Graph tags (product image).

ADMIN PANEL (Filament /admin)
8. ProductResource and CategoryResource (read-mostly) across all stores: store filter, search, action "Hide product" (status hidden_by_admin with reason) and "Restore".

VENDOR PANEL (Filament /vendor)
9. CategoryResource (tree/reorder, activate toggle).
10. ProductResource: form with name, category, description (rich text), prices, status; variants via Repeater/relation manager (size, color, SKU, stock, optional price); image uploader (reorderable, max 8, validates type/size); table with thumbnail, stock total, low-stock badge (<=3), status filter; bulk publish/unpublish.
11. Dashboard widget: products count, low-stock list.

TESTS
12. Isolation (products/categories/variants/media between stores, both panel and storefront), slug uniqueness per store, max images, stock reserve/release/commit incl. concurrent oversell attempt, published-only visibility, admin-hidden product not visible.

OUT OF SCOPE: cart, theme switching, CSV import.
```

---

## الفيز 4: محرك الثيمات (Theme Engine)

**الهدف:** الثيم مجلد بـ Manifest، التاجر يختاره ويضبط إعداداته ويعاينه، وإضافة ثيم جديد بدون لمس الـ Core.

**Definition of Done**

- [ ] الثيم الافتراضي يغطي كل الصفحات المطلوبة.
- [ ] التاجر يبدّل الثيم ويعدّل ألوانه وخطوطه فيتغير المتجر بدون Build.
- [ ] مجلد ثيم جديد + `php artisan theme:register` يظهر للتاجر مباشرة.

```text
[Paste MASTER CONTEXT first]

PHASE 4 - THEME ENGINE (prerequisite: Phases 1-3)

Goal: a theme system where a theme is a folder of Vue pages + a manifest, selectable and configurable per store.

THEME CONTRACT
- Location: resources/themes/{slug}/ with theme.json (name, slug, version, contract_version, preview image, supported pages, settings schema: color, select, range, toggle fields with defaults) and Vue pages. Required pages: Home, Category, Product, Cart, Checkout, Account, Orders, NotFound.
- Themes receive ONLY typed page props (versioned ViewModels/DTOs). They never query the DB or know Eloquent models.
- Settings are saved in stores.theme_settings (json) and converted to CSS variables at render time (--color-primary etc.). Vendors can change only: primary/secondary color, heading/body font (from an approved list of 3-5 Arabic fonts self-hosted), corner style (sharp|soft), plus theme-specific schema fields.
- Fallback: if a theme lacks a page, or targets an unsupported contract major, the `default` theme's page is used.
- Versioning: contract_version is semver (minor = additive props only, major = breaking). Each theme declares the version it targets; the platform supports the current major and the previous one. Only the platform team changes the contract, never a vendor or theme author.
- Assets: themes are lazy-loaded with import.meta.glob so a store loads only its theme.

BACKEND (module Theming + Storefront)
1. themes table (slug, name, version, is_active, manifest json) + artisan commands theme:register {slug} (validates theme.json), theme:list.
2. ThemeManager: resolves the active theme for the current store (cached under store:{id}:theme), ThemeSettings DTO validation against the schema, CSS variable generation.
3. Page DTOs in Storefront (HomePageData, CategoryPageData, ProductPageData, CartPageData, CheckoutPageData, AccountPageData, OrdersPageData) with a contract_version constant. Refactor Phase 3 controllers to return them.
4. Inertia page resolver: storefront/{theme}/{Page} with fallback to default.
5. Preview: ?preview_theme={slug} works only for the authenticated vendor owner of that store; it never changes the published theme.

FRONTEND
6. Build the `default` theme completely: layout (header with logo, categories nav, search, cart count; footer with contact/social), all required pages (Cart/Checkout/Account/Orders can render the DTO with placeholder content that later phases fill in), mobile-first, RTL, accessible (focus rings, 44px targets, aria labels in Arabic). Use the Design tokens as CSS variables only.
7. Write docs/THEME_CONTRACT.md explaining how to build/register a new theme.

ADMIN PANEL (Filament /admin)
8. ThemeResource: list of themes, activate/deactivate, version, preview image, action "Re-scan themes folder" (runs the registration logic).

VENDOR PANEL (Filament /vendor)
9. Page "Appearance": grid of active themes with preview images, select a theme, a settings form generated dynamically from theme.json (color pickers with contrast warning, selects, toggles), "Preview" button (opens ?preview_theme), "Publish" button. Show which theme is live.

TESTS
10. Manifest validation, settings schema validation, CSS variables output, fallback page behavior, preview permission (non-owner and guests blocked), publish changes only the owner's store, cache invalidation on change, isolation.

OUT OF SCOPE: additional themes (Phase 12), page builder.
```

---

## الفيز 5: حسابات العملاء

**الهدف:** العميلة تسجل وتدخل داخل كل متجر على حدة، وتدير عناوينها.

**Definition of Done**

- [ ] نفس الإيميل يسجل في متجرين كحسابين منفصلين.
- [ ] عميلة متجر A لا تقدر تستخدم جلستها في متجر B.
- [ ] الأدمن والتاجر يشوفوا العملاء المناسبين لهم قراءة فقط.

```text
[Paste MASTER CONTEXT first]

PHASE 5 - CUSTOMER ACCOUNTS (prerequisite: Phases 1-4)

Goal: per-store customer authentication and address book.

BACKEND (module Auth)
1. Complete the `customer` guard: register, login (customer identifier = email OR mobile phone + password; at least one is required at registration and each is unique per store; OTP out of scope), logout, forgot/reset password using a store-scoped broker (tokens only valid for that store's customer), optional email verification (config flag, off by default). Rate limit login/register/reset.
2. Middleware EnsureCustomerBelongsToStore: if the session's customer.store_id != Store::current()->id, log the customer out of that guard.
3. addresses table (store_id, customer_id, label, recipient_name, phone, governorate [Egypt governorates list in a config/enum], city, address_line, notes, is_default) + CRUD Actions and Policy (customer only touches own).
4. Intended-URL handling so guests sent to login from checkout return to checkout.

FRONTEND (default theme pages via the contract)
5. Pages: Login, Register, ForgotPassword, ResetPassword, Account (profile edit, change password), Addresses (list/add/edit/delete/set default). Header shows login/account state. Arabic validation messages, RTL forms, mobile first. Add the DTOs/props these pages need to the theme contract (bump contract_version minor) and update docs/THEME_CONTRACT.md.

ADMIN PANEL (Filament /admin)
6. CustomerResource: read-only list across stores (store filter, search by name/email/phone), view page with addresses; action Deactivate/Activate customer.

VENDOR PANEL (Filament /vendor)
7. CustomerResource (own store only): read-only list and view (name, phone, city, registration date). No export in this phase.

TESTS
8. Same email in two stores = independent accounts and passwords, reset token cannot be used cross-store, cross-store session invalidation, address ownership policy, throttling, deactivated customer cannot log in, vendor panel never lists other stores' customers, isolation.

OUT OF SCOPE: social login, OTP, orders.
```

---

## الفيز 6: الكارت والـ Checkout والطلبات

**الهدف:** العميلة تضيف للكارت وتعمل Checkout فينشأ طلب بحالة `pending_payment` مع حجز مخزون وعمولة محسوبة.

**Definition of Done**

- [ ] الكارت يندمج عند تسجيل الدخول والـ Checkout يتطلب تسجيل دخول.
- [ ] الطلب بيحجز المخزون 24 ساعة، والطلب غير المدفوع (`pending_payment`) بيتلغي تلقائيًا بعدها ويتحرر المخزون؛ الطلبات في `payment_review` مستثناة.
- [ ] كل `order_item` فيه نسبة العمولة وقيمتها متجمدتين.

```text
[Paste MASTER CONTEXT first]

PHASE 6 - CART, CHECKOUT & ORDERS (prerequisite: Phases 1-5)

Goal: customers can place an order that reserves stock and awaits payment. No payment handling yet.

BACKEND (module Sales)
1. Migrations/models (store_id + BelongsToStore): carts + cart_items (session_id or customer_id, variant_id, qty), orders (number, customer_id, status, subtotal, shipping_fee, total, currency default EGP, shipping address snapshot json, customer contact snapshot, expires_at, delivered_at nullable, completed_at nullable), order_items (variant_id, product name/sku/size/color/price snapshots, qty, line_total, commission_rate, commission_amount), order_status_logs (order_id, from, to, actor_type, actor_id, note), shipping_rates (store_id, kind flat|governorate, governorate nullable, fee).
2. Cart service: add/update/remove, validates stock, guest cart in session merged into the customer cart on login.
3. Action PlaceOrder (single transaction): validates cart and stock, resolves shipping fee from the store's rates, snapshots items and address, computes commission per item (rate = store override or platform default; rounding half-up per item, integer piasters), reserves stock through StockService with expires_at = now + stock_reservation_hours, creates the order as pending_payment, logs status, clears the cart, dispatches OrderPlaced.
4. OrderStateMachine: explicit allowed transitions with guards, every transition logged; invalid transitions throw. Statuses: pending_payment, payment_review, paid, processing, shipped, delivered, completed, cancelled, refunded.
5. Scheduled command orders:expire (every 5 minutes): cancel pending_payment orders past expires_at, release stock, log, dispatch OrderCancelled. Orders in payment_review are NEVER expired: the reservation timer is paused until the admin decides (see Phase 7 for the post-rejection window). A cart and an order belong to exactly one store. Order number generator unique per store (e.g. prefix + sequence).
6. Customers can cancel their own order only while pending_payment.

FRONTEND (default theme via contract)
7. Cart page or drawer (qty change, remove, totals), Checkout page as a stepper (1 address select/add, 2 shipping fee + payment method choice [Vodafone Cash | InstaPay], 3 review and confirm), order-created confirmation page showing order number and a placeholder component "PaymentInstructions" that Phase 7 will fill, Orders list and Order detail pages with status badges (StatusBadge colors: pending gray, review yellow, paid/processing blue, shipped purple, delivered/completed green, cancelled/refunded red).

ADMIN PANEL (Filament /admin)
8. OrderResource: all stores, filters (store, status, date), view page with items, totals, address, status timeline from logs; action Cancel (reason; releases stock). Platform Settings page already exists; add the commission default and reservation hours there if missing.

VENDOR PANEL (Filament /vendor)
9. Shipping rates settings page (flat fee or per-governorate table). OrderResource scoped to the store and ONLY orders in paid-or-later statuses (empty until Phase 7); implement the scope now with a test.

TESTS
10. Stock reservation/release, concurrent last-item purchase, commission snapshot math (including rounding), shipping fee resolution, state machine valid/invalid transitions, expiry command, guest cart merge, a customer cannot see/cancel another customer's or store's order, isolation.

OUT OF SCOPE: payment receipt, ledger, shipping actions.
```

---

## الفيز 7: الدفع اليدوي (Manual Payments)

**الهدف:** العميلة تحوّل وترفع الإيصال، والأدمن يراجع ويقبل أو يرفض من لوحته، والقبول يحوّل الطلب إلى `paid`.

**Definition of Done**

- [ ] العميلة ترى بيانات حساب المنصة والمبلغ بالضبط وترفع إيصال.
- [ ] الأدمن يقبل أو يرفض من قائمة المراجعة، والقبول يطلق الحدث `PaymentApproved` مرة واحدة فقط.
- [ ] الإيصالات لا تُفتح إلا برابط موقّع للأدمن، والتاجر لا يراها.

```text
[Paste MASTER CONTEXT first]

PHASE 7 - MANUAL PAYMENTS (prerequisite: Phases 1-6)

Goal: receipt upload by customers and a review queue for admins. Approval moves the order to paid.

BACKEND (module Payments)
1. Tables: payment_methods (platform-level, NO store_id: type vodafone_cash|instapay, account_name, account_number/handle, instructions, is_active), payments (store_id, order_id, method, amount, status pending_review|approved|rejected, receipt_path, transfer_reference nullable, sender_number nullable, attempt int, reviewed_by, reviewed_at, reject_reason, duplicate_of nullable).
2. Action SubmitReceipt: only for the customer's own pending_payment order; allowed types jpg/png/webp/pdf up to 5MB; store on a PRIVATE disk (receipts/{store_id}/{order_id}/...); re-encode images; attempt limit = 3 TOTAL submissions (the third rejected one is final; an admin can still cancel the order); moves order to payment_review and pauses its expiry timer. Detect duplicate transfer_reference across all stores: flag the payment (duplicate_of) but do not block.
3. Actions ApprovePayment and RejectPayment(reason): single transaction, idempotent (a second approve does nothing), actor recorded. Approve: payment approved, order -> paid (state machine), stock reservation committed via StockService, dispatch PaymentApproved(order, payment). Reject: payment rejected, order back to pending_payment with a NEW, shorter reservation window (not a fresh 24h) of payment_retry_window_hours (platform setting, default 6), customer may resubmit while attempts remain.
4. Receipt access only through temporary signed URLs for admins and for the owning customer; vendors never get access. Policy + test.
5. Notifications (queued; mail + database): customer (receipt received, approved, rejected with reason), admin (new receipt waiting), vendor (new paid order). Arabic templates in lang files.

FRONTEND (default theme via contract)
6. After placing the order, a PaymentInstructions component: chosen method's account details with copy buttons, the exact amount, the order reference, remaining time before expiry, and a receipt upload form (preview, progress, optional transfer reference and sender number). Order detail page shows payment status (under review / approved / rejected with reason + re-upload button when attempts remain).

ADMIN PANEL (Filament /admin)
7. PaymentResource as the review queue: default filter pending_review sorted oldest first, navigation badge with pending count, receipt preview modal (image or PDF via signed URL), side-by-side order amount vs expected, duplicate warning badge, actions Approve / Reject (reason required). Widget: receipts waiting longer than payment_review_sla_hours, with an "overdue" flag past payment_review_overdue_hours. Reviews are never auto-cancelled; they only escalate.
8. PaymentMethodResource to manage the platform's Vodafone Cash / InstaPay accounts.

VENDOR PANEL (Filament /vendor)
9. The paid-order scope now shows data: orders appear when they reach paid. Database notification "new paid order". Vendor cannot see receipts or payment details beyond status.

TESTS
10. Approval/rejection flow and idempotency, attempts limit, file validation, signed URL authorization (admin yes, owning customer yes, vendor no, other customer no), duplicate reference flag, expiry interplay (cannot submit after the order expired), event dispatched exactly once, isolation.

OUT OF SCOPE: ledger entries (Phase 8 listens to PaymentApproved), online payment gateway (only define a PaymentGateway contract interface and a ManualPaymentGateway stub).
```

---

## الفيز 8: الـ Ledger والعمولة

**الهدف:** كل فلوس المنصة بتتسجل قيود غير قابلة للتعديل، ورصيد كل تاجر (Pending / Available / Paid) صحيح دايمًا.

**Definition of Done**

- [ ] طلب 1,200 منتجات + 60 شحن بعمولة 10% يعطي: نقدية 1,260، مستحق التاجر 1,140، عمولة 120.
- [ ] لا يمكن تعديل أو حذف أي قيد.
- [ ] أمر `ledger:reconcile` يعطي صفر فروقات ويُنبّه الأدمن عند أي فرق.

```text
[Paste MASTER CONTEXT first]

PHASE 8 - LEDGER & COMMISSION (prerequisite: Phases 1-7)

Goal: an append-only ledger driven by events, vendor balances, and a daily reconciliation.

BACKEND (module Ledger)
1. ledger_entries (store_id nullable for platform-only accounts, order_id nullable, payout_id nullable, account enum, direction debit|credit, amount integer piasters, reverses_entry_id nullable, available_at nullable, idempotency_key unique, meta json, created_at). Accounts: platform_cash, vendor_payable_pending, vendor_payable_available, vendor_payout_pending, platform_commission_revenue. The Eloquent model must block update and delete (throw), and add a DB-level protection if the database allows it.
2. Listener on PaymentApproved (idempotent via idempotency_key): debit platform_cash for the order total; credit vendor_payable_pending for (sum of product lines - commission) + shipping fee; credit platform_commission_revenue for the commission total. Total debits must equal total credits per posting group. Commission comes from the snapshotted order_items.
3. Scheduled job ReleaseAvailableFunds (hourly): for orders delivered at least return_window_days ago and not refunded, move the amount from vendor_payable_pending to vendor_payable_available (idempotent), and mark the order completed through the state machine. (Delivery is built in Phase 9; the job must already work with orders.delivered_at.)
4. ReversalService used by refunds (Phase 9): creates opposite entries referencing reverses_entry_id; supports partial amounts with proportional commission.
5. BalanceService: per store pending, available, payout_pending, paid (computed from entries, cached with invalidation), plus platform totals.
6. Command ledger:reconcile (daily): asserts sum(debits)=sum(credits); platform_cash = payables (pending+available+payout_pending) + commission revenue; per-order consistency with orders table. Stores the result in a reconciliations table and notifies admins on any mismatch.

FRONTEND
7. No storefront work in this phase. Confirm nothing about commission leaks into customer-facing responses (add a test).

ADMIN PANEL (Filament /admin)
8. LedgerEntryResource: read-only, filters (store, order, account, date), no create/edit/delete actions.
9. Page "Vendor balances": table of stores with pending, available, payout pending, paid, and a drill-down to entries. Widget "Reconciliation": last run status and mismatch details, plus a manual "Run now" action.
10. Commission settings: default rate in Platform Settings (already there) and the per-store override on StoreResource (validate 0-50%).

VENDOR PANEL (Filament /vendor)
11. Dashboard widgets: pending, available, paid balances. Page "Statement": table of the store's own entries (date, order, description in Arabic, amount, running balance), date-range filter. Order view shows the vendor's net amount for that order (never the customer's payment details).

TESTS
12. The worked example above to the piaster; rounding on multiple items; idempotent listener (event fired twice = one posting group); update/delete blocked; reversal full and partial; availability job timing; property-style test: random orders keep reconciliation at zero; isolation (vendor sees only own entries).

OUT OF SCOPE: payouts (Phase 10), refunds UI (Phase 9).
```

---

## الفيز 9: الشحن والتسليم والاسترجاع

**الهدف:** التاجر يجهّز ويشحن يدويًا ويسجل التسليم، والعميلة تتتبع طلبها، والأدمن يقدر يسترجع.

**Definition of Done**

- [ ] التاجر يغيّر الحالة processing ثم shipped (اسم الشركة + رقم التتبع) ثم delivered.
- [ ] العميلة ترى خط زمني للطلب ورقم التتبع وتقدر تؤكد الاستلام.
- [ ] الاسترجاع الكامل والجزئي من الأدمن يعكس قيود الـ Ledger بشكل صحيح.

```text
[Paste MASTER CONTEXT first]

PHASE 9 - SHIPPING, DELIVERY & REFUNDS (prerequisite: Phases 1-8)

Goal: manual fulfillment by vendors, tracking for customers, refunds by admins.

BACKEND (module Shipping + Sales)
1. shipments (store_id, order_id, courier_name free text, tracking_number nullable, tracking_url nullable, note, shipped_at, delivered_at). Contract ShippingProvider (create, track) with ManualShippingProvider implementation; the provider is resolved from config so a gateway can be plugged in later.
2. Actions (each uses the state machine and logs actor): StartProcessing (paid -> processing), MarkShipped (processing -> shipped, requires courier_name), MarkDelivered (shipped -> delivered, sets orders.delivered_at, dispatches OrderDelivered), ConfirmReceipt by the customer (shipped -> delivered). Whichever happens first sets delivered_at (and starts the return window); it is never overwritten. Vendors can act only on their own store's orders.
3. Scheduled orders:complete is already covered by ReleaseAvailableFunds; verify it integrates (delivered + return_window_days -> completed).
4. RefundOrder (admin only; no automatic right of return and no goods-return tracking): full or partial amount with a reason from a fixed list (wrong_size, changed_mind, defective, not_delivered, other) plus an optional note; allowed from paid/processing/shipped/delivered (not completed unless admin overrides with a reason); order -> refunded (or keeps status with a refund record for partial), optional restock toggle, calls ReversalService, dispatches OrderRefunded, notifies customer and vendor. Table refunds (order_id, amount, reason, restocked, actor).

FRONTEND (default theme via contract)
5. Order detail: status timeline built from order_status_logs, courier + tracking number (link if tracking_url), "I received my order" button when shipped, store contact block for problems/returns. Update DTOs and bump the theme contract minor version.

ADMIN PANEL (Filament /admin)
6. OrderResource actions: Refund (full/partial with reason, restock toggle), Force status change (reason required, audited), Shipments relation manager. Add an audit trail entry for each admin override.

VENDOR PANEL (Filament /vendor)
7. OrderResource actions: Start processing, Mark shipped (modal: courier name, tracking number, optional URL, note), Mark delivered; status tabs with counts ("to ship", "shipped"); a printable packing slip PDF action (Arabic RTL). Notification to the customer on shipped/delivered.

TESTS
8. Transition permissions (only own store, only valid prior states), required fields, delivered_at set and consumed by the availability job, refund math with ledger reversal (full and partial, commission proportional), restock effect, customer cannot confirm another customer's order, isolation.

OUT OF SCOPE: shipping gateway integration, returns workflow beyond refund.
```

---

## الفيز 10: الصرف والتقارير (Payouts & Reports)

**الهدف:** التاجر يطلب صرف مستحقاته، والأدمن يحوّل ويسجل، مع Dashboards وتقارير للطرفين.

**Definition of Done**

- [ ] التاجر لا يقدر يطلب أكثر من الرصيد Available، ولا أقل من الحد الأدنى.
- [ ] الأدمن يسجل التحويل برقم العملية فيتحرك الرصيد إلى Paid بقيود صحيحة.
- [ ] كشف الحساب وتقرير الإيرادات يتصدّروا CSV.

```text
[Paste MASTER CONTEXT first]

PHASE 10 - PAYOUTS & REPORTS (prerequisite: Phases 1-9)

Goal: vendor payouts and reporting dashboards.

BACKEND (module Ledger)
1. payouts (store_id, amount, status requested|paid|rejected|cancelled, transfer_method vodafone_cash|instapay|bank, destination snapshot, transfer_reference nullable, proof_path nullable, requested_at, processed_by, processed_at, reject_reason). Platform setting min_payout_amount.
2. Store payout details (method + number/handle/IBAN), stored ENCRYPTED, editable by the vendor.
3. Actions (transactions, locking per store): RequestPayout (rejected while the store is suspended; vendor chooses any amount between min_payout_amount and available; partial payouts allowed; one open request per store; posts entries vendor_payable_available debit / vendor_payout_pending credit), CancelPayout by vendor (reverses), MarkPayoutPaid by admin (requires transfer_reference, optional proof upload; posts vendor_payout_pending debit / platform_cash credit), RejectPayout (reverses with reason). Idempotent and audited.
4. Reporting queries/services: sales by day, orders by status, top products, GMV, commission revenue by day/store, payouts pending. Cached briefly with store-prefixed keys where store-scoped.
5. CSV exports: store statement (date range) and platform revenue (date range, per store).

FRONTEND
6. No storefront work in this phase.

ADMIN PANEL (Filament /admin)
7. PayoutResource: queue of requested payouts (navigation badge), view with destination details (decrypted only for admins), actions Mark as paid (transfer reference + proof), Reject. 
8. Admin dashboard widgets: GMV (30 days), commission revenue, receipts awaiting review, payouts awaiting, orders by status chart, top stores. Reports page: revenue by period/store with export.

VENDOR PANEL (Filament /vendor)
9. Page "Payouts": payout details form, request payout action (shows available balance, validates), history with statuses. Dashboard widgets: sales last 30 days chart, orders to ship, top products, balances. Statement CSV export.

TESTS
10. Over-request blocked, below-minimum blocked, concurrent requests cannot exceed balance, ledger postings for request/paid/reject/cancel balance to zero, encryption at rest, only admins see destination, vendor sees own payouts only, export contents, reconciliation stays at zero after a full cycle, isolation.

OUT OF SCOPE: automatic transfers, accounting integrations.
```

---

## الفيز 11: الرضا والإشعارات

**الهدف:** استبيان رضا تلقائي بعد التسليم، وتنبيه عند التقييم المنخفض، وقوالب إشعارات موحدة.

**Definition of Done**

- [ ] بعد يومين من التسليم يصل للعميلة رابط استبيان موقّع.
- [ ] الاستبيان يُرسل مرة واحدة فقط، والرابط لا يعمل لو اتعدّل أو انتهى.
- [ ] تقييم 2 أو أقل ينبّه الأدمن والتاجر.

```text
[Paste MASTER CONTEXT first]

PHASE 11 - SATISFACTION SURVEYS & NOTIFICATIONS (prerequisite: Phases 1-10)

Goal: post-delivery satisfaction survey, low-rating alerts, consistent notification templates.

BACKEND (modules Feedback + Notifications)
1. satisfaction_surveys (store_id, order_id unique, token, overall_rating 1-5, product_match_rating 1-5, delivery_speed_rating 1-5, comment, sent_at, submitted_at, low_rating_flag). Created when an order is delivered (listener on OrderDelivered), but sent only after survey_delay_days. The survey is independent of the return window.
2. Scheduled surveys:send (hourly): finds due, unsent surveys, sends the Arabic email with a signed URL (expires in 30 days); idempotent; a NotificationChannel interface exists for a future WhatsApp channel (no integration now, but the link must be easy to copy by the vendor/admin).
3. SubmitSurvey action: single submission, validation, sets low_rating_flag when overall <= 2 and dispatches LowRatingReceived (alerts admin and vendor only; it never triggers a refund or changes the order status); completed orders keep working normally.
4. Notifications module: centralize all Arabic templates (order placed, receipt received/approved/rejected, shipped, delivered, survey invitation, payout requested/paid/rejected, low rating, reconciliation mismatch), queued, with mail + Filament database notifications; one place to list events -> recipients -> templates; tests that every event has a template.

FRONTEND
5. Survey page (public via signed URL; also reachable from the order detail while pending): large star inputs, three ratings + comment, thank-you state, expired/invalid link state; mobile first RTL; styled by the store's theme tokens. Update contract DTOs.

ADMIN PANEL (Filament /admin)
6. SurveyResource: all stores, filters (store, rating, date, flagged), view with comment and order link. Widgets: average rating per store (30 days), response rate, flagged list.

VENDOR PANEL (Filament /vendor)
7. Page "Customer feedback": own surveys table with filters, average rating widgets, trend chart, flagged items highlighted; database notification on low rating.

TESTS
8. Delay logic, single send, signed URL tamper/expiry, single submission, low-rating event + notifications to the correct recipients only, vendor sees own store's surveys only, template coverage test, isolation.

OUT OF SCOPE: public product reviews (V2), WhatsApp integration.
```

---

## الفيز 12: التثبيت والإطلاق (Hardening & Launch)

**الهدف:** ثيمان إضافيان، أمان وأداء وSEO، اختبار شامل من الشراء للصرف، وتجهيز الـ Pilot مع 2 إلى 3 تجار.

**Definition of Done**

- [ ] اختبار E2E واحد يمر بالرحلة الكاملة: شراء، دفع، قبول، شحن، تسليم، استبيان، صرف.
- [ ] مراجعة الأمان والـ Policies لا تترك ثغرات (قائمة الفحص تعدي).
- [ ] نشر تجريبي شغّال بنسخ احتياطي ومراقبة، وبيانات Demo جاهزة.

```text
[Paste MASTER CONTEXT first]

PHASE 12 - HARDENING, EXTRA THEMES & LAUNCH (prerequisite: Phases 1-11)

Goal: make the MVP safe, fast, polished and deployable for a pilot with 2-3 vendors.

BACKEND / SECURITY
1. Security audit tasks: verify a Policy exists and is enforced for every model and Filament resource; add an automated test (or Pest architecture test) that fails if code outside the admin context calls withoutGlobalScopes/the scope-disable helper; rate limits on login, register, receipt upload, survey submit; security headers (CSP appropriate for Inertia/Vite and Filament, HSTS, X-Frame-Options); file upload hardening review; make sure receipts and payout proofs are never publicly reachable.
2. Enable two-factor/MFA for admin accounts (Filament's built-in support if available in the installed version, otherwise a well-known package) and an audit log for payment decisions, refunds, forced status changes, payout decisions, commission/slug changes (who, when, before/after). Admin page to browse the audit log.
3. Performance: Model::preventLazyLoading in non-production, fix N+1s found by the E2E run, indexes ((store_id,status) on orders, (store_id,slug) on products/categories, ledger query indexes), caching for store settings, theme, categories and product listings with store-prefixed keys and invalidation, queue workers config (Horizon or Supervisor), image sizes verified.
4. Operations: spatie/laravel-backup (database + receipts disk) scheduled daily with a documented restore test, error tracking (Sentry or equivalent), structured logging for financial events, health page.

FRONTEND
5. Build TWO additional themes (e.g. `classic` and `minimal`) that follow the theme contract and docs/THEME_CONTRACT.md without touching core code; prove it by registering them with theme:register only.
6. SEO: per-store and per-product meta tags, Open Graph, canonical URLs, Inertia SSR if feasible within the setup (otherwise document the decision), 404/500 pages in Arabic. Accessibility pass (axe or Lighthouse) and RTL QA on all theme pages at 360, 768 and 1280 widths.

ADMIN & VENDOR PANELS (Filament)
7. Admin: System health page (queue status, last scheduler run, last reconciliation, failed jobs), final Platform Settings review, audit log viewer.
8. Vendor: onboarding checklist widget (logo uploaded, first product published, theme chosen, shipping rates set, payout details saved) that disappears when complete.
9. Custom Filament theme for both panels aligned with the platform's brand tokens (colors, font), RTL verified.

DEMO DATA & DEPLOY
10. Seeders: 2 demo stores with products, variants, images placeholders, one of each order status, to demo every screen. Command to wipe demo data safely.
11. Deployment docs (docs/DEPLOYMENT.md): server requirements, .env.example with all variables, queue + scheduler (cron) setup, build steps, zero-downtime deploy script, rollback, backup/restore, first-run checklist.
12. Pilot plan (docs/PILOT.md): a launch-gate checklist (an accountant and a lawyer must review the money-holding model, commission invoice and vendor terms of use before ANY real customer money is accepted; owner: product owner, not a coding task), onboarding steps for a vendor, test script for the first 20 real orders, what to monitor, and an incident checklist for payment/ledger issues.

TESTS
13. One end-to-end test (Pest browser tests, Dusk or Playwright) for the full journey: vendor onboarding -> product publish -> customer register -> checkout -> receipt upload -> admin approve -> vendor ship -> delivered -> survey submit -> funds available -> payout request -> admin marks paid -> reconciliation = 0. Complete the isolation suite so every tenant-owned model and every Filament resource has a cross-store test.

OUT OF SCOPE: new features. Anything discovered goes to a V2 backlog file.
```

---

## بعد الـ MVP (V2)

1. بوابة دفع أونلاين عبر `PaymentGateway`.
2. بوابة شحن عبر `ShippingProvider` مع تتبع تلقائي.
3. Custom domain أو Subdomain بإضافة Resolver جديد.
4. موظفو التاجر وصلاحياتهم، وتسجيل التجار الذاتي.
5. تقييمات عامة للمنتجات، كوبونات، Page builder للثيمات.
