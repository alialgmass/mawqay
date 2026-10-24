# Phase 3: Catalog

**Status:** ready-for-agent
**Prerequisite:** Phases 1–2 closed. Source: `docs/project-phases-ai-prompts.md`, Phase 3. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

A Store has an identity but nothing to sell. A Vendor Owner cannot organise Categories, add Products with sizes, colours and photos, or control what is public. A Platform Admin cannot see or take down an offending Product. A visitor opening a Storefront sees no goods, cannot browse, search or pick a size and colour. Phase 6 (cart and checkout) also has nothing safe to reserve stock against.

## Solution

A Vendor Owner manages Categories (two levels) and Products from the Vendor Panel: description, prices, Variants (size, colour, SKU, stock, optional price), and up to eight reorderable images. Products start Draft and become public when published. A Platform Admin sees every Store's Products and can hide one with a reason and restore it. Visitors browse a Store's Home, Category pages, Product pages and name search, pick a Variant with unavailable ones disabled, and see optimised images. Stock Reservation is built and proven now (including under concurrency) so Phase 6 can consume it.

## User Stories

1. As a Vendor Owner, I want to create top-level Categories, so that I can organise my Products.
2. As a Vendor Owner, I want to create a sub-Category under a top-level one, so that I can refine the grouping.
3. As a Vendor Owner, I want the tree limited to two levels, so that navigation stays simple.
4. As a Vendor Owner, I want to reorder Categories, so that the most important appear first.
5. As a Vendor Owner, I want to activate or deactivate a Category, so that I can hide a section without deleting it.
6. As a Vendor Owner, I want a Category slug unique within my Store only, so that I can reuse a name another Store already uses.
7. As a Vendor Owner, I want to be stopped from deleting a Category that still has Products, so that Products are never orphaned.
8. As a Vendor Owner, I want to create a Product with name, Category, description, base price and optional sale price, so that customers see what I sell and at what price.
9. As a Vendor Owner, I want a rich-text description, so that I can format details like fabric and care.
10. As a Vendor Owner, I want the sale price rejected if it is not lower than the base price, so that I cannot publish a nonsensical discount.
11. As a Vendor Owner, I want a Product slug unique within my Store, so that its address is predictable.
12. As a Vendor Owner, I want a new Product to start as Draft, so that nothing is public until I choose.
13. As a Vendor Owner, I want to publish and unpublish a Product, so that I control visibility.
14. As a Vendor Owner, I want to publish or unpublish many Products at once, so that launching a collection is quick.
15. As a Vendor Owner, I want to be unable to publish a Product with no Variant or no image, so that customers never see an unbuyable item.
16. As a Vendor Owner, I want to add Variants with size, colour, SKU, stock and optional price override, so that each purchasable combination is tracked.
17. As a Vendor Owner, I want a size and colour combination to be unique within a Product, so that the picker is unambiguous.
18. As a Vendor Owner, I want a SKU unique within my Store, so that I can identify items in my own system.
19. As a Vendor Owner, I want stock to be a non-negative whole number, so that counts stay valid.
20. As a Vendor Owner, I want to edit stock of a Variant directly, so that I can correct counts after a delivery.
21. As a Vendor Owner, I want to upload up to eight images per Product, so that customers can see every angle.
22. As a Vendor Owner, I want a ninth image refused with a clear message, so that I understand the limit.
23. As a Vendor Owner, I want only valid image types and sizes accepted, so that bad files never enter the Store.
24. As a Vendor Owner, I want to reorder images and the first to be the cover, so that I choose the main picture.
25. As a Vendor Owner, I want images kept under my Store's own folder, so that they are isolated from other Stores.
26. As a Vendor Owner, I want my uploads automatically resized into thumbnail, medium and large WebP versions, so that pages load fast on mobile.
27. As a Vendor Owner, I want the original upload preserved, so that I can re-derive sizes later.
28. As a Vendor Owner, I want a Product list with thumbnail, total stock, low-stock badge (3 or fewer), and status filter, so that I can spot what needs attention.
29. As a Vendor Owner, I want a dashboard widget with my Product count and low-stock list, so that I restock in time.
30. As a Vendor Owner, I want to be unable to see or edit another Store's Categories, Products, Variants or images, so that data stays private.
31. As a Vendor Owner, I want a Product hidden by the Platform to show as such with its reason, so that I understand why it vanished.
32. As a Vendor Owner, I want to be unable to republish a Product the Platform hid, so that moderation cannot be bypassed.
33. As a Platform Admin, I want to list all Products and Categories across Stores, so that I can oversee the Platform.
34. As a Platform Admin, I want to filter and search by Store, name and status, so that I find an item quickly.
35. As a Platform Admin, I want to hide a Product with a mandatory reason, so that violations are removed and explained.
36. As a Platform Admin, I want to restore a hidden Product, so that a resolved case returns to normal.
37. As a Platform Admin, I want the Catalog resources to be read-mostly, so that I moderate without editing a Vendor's content.
38. As a visitor, I want the Store Home to show latest published Products and top-level Categories, so that I can start browsing.
39. As a visitor, I want a Category page with pagination, so that long lists stay manageable.
40. As a visitor, I want to sort by newest or price, so that I find what suits me.
41. As a visitor, I want a Category page to include Products of its sub-Categories, so that a top-level Category is not empty.
42. As a visitor, I want a Product page with gallery, price, sale price and description, so that I can decide.
43. As a visitor, I want a size and colour picker where out-of-stock choices are disabled, so that I cannot choose what is unavailable.
44. As a visitor, I want the price to reflect the chosen Variant's override, so that I see the true price.
45. As a visitor, I want to search Products by name within a Store, so that I find an item directly.
46. As a visitor, I want a friendly empty state for no results or an empty Category, so that I am not left on a blank page.
47. As a visitor, I want breadcrumbs on Category and Product pages, so that I can navigate up.
48. As a visitor, I want Draft, hidden and inactive-Category Products to be invisible, so that I only see buyable goods.
49. As a visitor, I want a direct link to a Draft or hidden Product to show not-found, so that withheld items are not exposed.
50. As a visitor, I want prices in Egyptian pounds shown consistently, so that amounts are clear.
51. As a visitor, I want right-to-left, mobile-first pages, so that browsing on my phone is comfortable.
52. As a visitor, I want each page to have a proper title, description and sharing preview with the Product image, so that links look good when shared.
53. As a visitor, I want a Store's Products never to appear in another Store's pages or search, so that each shop stays its own.
54. As a developer, I want a stock service that reserves, releases and commits quantities atomically, so that Phase 6 can rely on it.
55. As a developer, I want reserving beyond available stock to fail, so that overselling is impossible.
56. As a developer, I want concurrent reservations of the last unit to result in exactly one success, so that races cannot oversell.
57. As a developer, I want release and commit to be bounded by what is reserved, so that counts never go inconsistent.
58. As a developer, I want a search interface with a simple name-matching implementation behind it, so that a better engine can replace it later.
59. As a developer, I want prices exposed as the Core money value, so that every module handles currency the same way.
60. As a developer, I want the isolation helper to cover the new Store-owned data, so that the Phase 1 guarantee keeps holding.

## Implementation Decisions

- **Modules touched.** A new Catalog module (categories, products, variants, media, stock service, search interface) per the PRD module map, depending only on Core and Tenancy. Backoffice gains the Admin and Vendor Panel resources and widget. Storefront gains catalog pages and components and depends on Catalog; dependency direction stays one-way and Catalog never reaches upward.
- **Data model.** Categories (parent, name, slug, sort, active), Products (Category, name, slug, description, base price, optional sale price, status Draft/Published/Hidden-by-admin, sort, hide reason), Variants (Product, SKU, size, colour, optional price override, stock, reserved defaulting to zero) and Product media (Product, path, sort). All carry the Store and use the Phase 1 tenant-scoping concern. Slugs unique per Store; SKUs unique per Store; size+colour unique per Product. Money stored as piasters integers, exposed as the Core money value.
- **Category tree.** Exactly two levels, enforced in the domain, not just the form. A Category with Products or children cannot be deleted. A top-level Category's listing includes its children's Products. Inactive Categories and their Products are hidden from the Storefront.
- **Product status.** Draft → Published → Draft by the Vendor; any → Hidden-by-admin by the Platform Admin with a mandatory reason; Hidden → previous visibility only through Admin restore (to Draft). A Vendor cannot publish a Hidden Product. Publishing requires at least one Variant and one image. A Product is public only when Published, in an active Category, in an Active Store.
- **Images.** Maximum eight per Product enforced in the domain. Stored under the Store's folder; the original is kept. An async job derives thumb, medium and large WebP versions. Until derived, the Storefront falls back to the original so a fresh upload is never broken. Library choice follows what the project already ships; adding a dependency needs approval.
- **Stock service.** Available = stock − reserved. `reserve`, `release`, `commit` run in a transaction with row-level locking on the Variant. Reserve fails if quantity exceeds available; release cannot exceed reserved; commit decrements both stock and reserved. Built and tested now, consumed by Phase 6. Reservation expiry timing and the payment-review exception (ADR 0002) belong to Phase 6 and are not built here.
- **Search.** A search interface with one name-matching implementation scoped to the current Store's published Products. Wildcard characters in the query are escaped.
- **Query scopes.** Published (including active-Category and trading rules) and in-stock scopes are the single definition of "visible" and "buyable" used by Storefront pages.
- **Storefront.** Pages: Home, Category, Product, Search, each with the default look only (Theme engine is Phase 4). Components: product card, variant picker, money display, breadcrumbs, empty state. Controllers hand the pages plain, typed data, not models, to ease the Phase 4 contract. SEO: title, meta description and Open Graph tags (Product image on Product pages).
- **Admin Panel.** Read-mostly Product and Category resources across all Stores with a Store filter, search, and Hide (with reason) and Restore actions. These call the same domain actions the rules live in.
- **Vendor Panel.** Category resource with reorder and activate toggle; Product resource with a form (rich-text description, prices, status), a Variants repeater, a reorderable image uploader with type/size/count validation, a table with thumbnail, total stock, low-stock badge (3 or fewer) and status filter, bulk publish/unpublish; a dashboard widget with Product count and low-stock list. The Panel's tenant scope remains the second isolation layer.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies (ADR 0001); PHPUnit replaces Pest wording as before.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: given this request, panel interaction or call, what response, stored data or visible page results. Do not assert class structure, queue internals or which helper ran.
- **Seam 1: HTTP and panel boundary (primary).** Feature tests through Storefront routes, the Vendor Panel and the Admin Panel. Cases:
  - a Vendor adds Categories (two-level limit, per-Store slug uniqueness, delete refusal) and a Product with Variants and images, publishes it, and it appears at the Storefront; Draft, hidden, inactive-Category and Suspended-Store Products do not;
  - validation: sale price, SKU and size+colour uniqueness, non-negative stock, ninth image refused, bad file types, publish without Variant or image;
  - Store A's owner cannot see or change Store B's data in the Panel; Store B's Products never surface on Store A's pages or search;
  - Admin sees all Stores' Products, hides with a mandatory reason, the Product leaves the Storefront, the Vendor cannot republish, Admin restores;
  - Category listing sort, pagination, sub-Category inclusion, Variant picker disabling out-of-stock choices, Variant price override, search matching and escaping, empty states, SEO tags;
  - image upload yields derived sizes once the job runs and the original is retained.
- **Seam 2: the stock service (pure-ish).** Direct tests of reserve/release/commit: success, oversell refusal, bounds, and a concurrency test where parallel attempts on the last unit yield exactly one success. This is the only unit-level seam, justified because Phase 6 depends on its guarantees.
- **Isolation.** Reuse the Phase 1 isolation helper for Categories, Products, Variants and media.
- **Framework and gate.** PHPUnit, no new test dependency; formatting and tests must pass. The concurrency test needs a database engine that honours row locks; if the test database cannot, state so rather than skip silently.
- **Prior art.** Phase 1 and 2 feature tests for panel access, tenancy scope, slug rules and the Storefront shell.

## Out of Scope

- Cart, checkout, Orders, expiry of reservations (Phase 6 and later).
- Theme selection and Theme Settings, Theme contract versioning (Phase 4).
- CSV import (V2), product reviews, wishlists, discounts beyond the single sale price.
- Staff roles under a Vendor Owner (V2), inventory history or adjustment logs.
- Better-than-name search (full-text, typo tolerance).
- Shipping weight/dimensions and tax.

## Further Notes

- **Tracker.** As in Phases 1–2, no tracker is configured and the repo has no git remote, so this spec lives in `docs/specs/` and tickets go to `.scratch/phase-3-catalog/issues/` with `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real tracker.
- **Seams to confirm.** One primary seam (HTTP and panel boundary) plus a small stock-service seam, reusing the existing isolation helper. Say so if you want different seams.
- **Suggested ticket order.** Catalog module and data model; Category management; Product and Variant management; image pipeline; stock service with concurrency test; visibility scopes and search; Storefront Home and Category; Product page and picker; SEO; Admin moderation; Vendor dashboard widget; phase gate.
- **Open assumptions.** Hidden-by-admin restores to Draft, not Published; publishing requires a Variant and an image; reserved stock is not editable by the Vendor; low-stock threshold is fixed at 3.
