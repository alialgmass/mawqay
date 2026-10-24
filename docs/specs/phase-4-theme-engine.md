# Phase 4: Theme Engine

**Status:** ready-for-agent
**Prerequisite:** Phases 1–3 closed. Source: `docs/project-phases-ai-prompts.md`, Phase 4. Terms follow `docs/GLOSSARY.md`.

## Problem Statement

Every Storefront looks the same: one hard-wired layout whose only variable is two colours. A Vendor Owner cannot choose a different look, pick fonts or corner style, or try a change before customers see it. The Platform team cannot add a new look without editing core Storefront code, and nothing stops a layout from reaching into the database. Pages are also tied to the current ad-hoc data shapes, so any change risks breaking every look at once.

## Solution

A Theme becomes a self-contained folder: a manifest plus a Vue page for each Storefront page. The Platform registers Themes with one command, and the Platform Admin can see and switch them on or off. A Vendor Owner opens an "Appearance" page, chooses from the active Themes, tunes the colours, fonts, corner style and any Theme-specific options, previews the result on their live Store without publishing, and then publishes. Themes receive only versioned, plain page data and never touch the database. If a Theme is missing a page or targets an unsupported contract, the default Theme's page is shown instead. The complete default Theme ships with this phase.

## User Stories

1. As a Platform Admin, I want to register a Theme by adding its folder and running one command, so that a new look needs no change to core code.
2. As a Platform Admin, I want a Theme's manifest validated when it is registered, so that a broken Theme cannot reach a Store.
3. As a Platform Admin, I want a clear list of what is wrong with an invalid manifest, so that the Theme author can fix it.
4. As a Platform Admin, I want to list registered Themes from the command line, so that I can see what the Platform offers.
5. As a Platform Admin, I want to see all Themes in the Admin Panel with name, version and preview image, so that I can oversee them.
6. As a Platform Admin, I want to activate or deactivate a Theme, so that I control which looks Vendors can choose.
7. As a Platform Admin, I want a "re-scan Themes folder" action, so that I can register new Themes without a terminal.
8. As a Platform Admin, I want re-scanning to update the version of an existing Theme, so that upgrades are picked up.
9. As a Platform Admin, I want the default Theme to be impossible to deactivate or remove, so that every Store always has a working fallback.
10. As a Platform Admin, I want a Store whose Theme is deactivated to keep rendering with the default Theme, so that deactivation never breaks a live Store.
11. As a Platform Admin, I want Theme registration to be idempotent, so that running it twice changes nothing.
12. As a Vendor Owner, I want an Appearance page listing the active Themes with preview images, so that I can pick the look I like.
13. As a Vendor Owner, I want to see which Theme is currently live, so that I know what customers see.
14. As a Vendor Owner, I want to choose a Theme and have its settings form appear, so that I can tailor it.
15. As a Vendor Owner, I want the settings form built from the Theme's manifest, so that every Theme offers its own options without custom code.
16. As a Vendor Owner, I want colour pickers, selects, ranges and toggles in that form, so that each option has the right control.
17. As a Vendor Owner, I want to choose my primary and secondary colours, so that the Storefront matches my brand.
18. As a Vendor Owner, I want a warning when my primary colour reads poorly against button text, so that I avoid an unreadable Store.
19. As a Vendor Owner, I want readable black or white text chosen automatically on my colours, so that buttons stay legible.
20. As a Vendor Owner, I want to choose a heading font and a body font from a short approved list of Arabic fonts, so that my Store has its own voice without slowing down.
21. As a Vendor Owner, I want to choose sharp or soft corners, so that the Store feels the way I want.
22. As a Vendor Owner, I want defaults from the Theme when I have not chosen a value, so that a new Theme looks good immediately.
23. As a Vendor Owner, I want invalid values rejected (a colour that is not a colour, a choice not in the list, a number outside the range), so that I cannot break my Store.
24. As a Vendor Owner, I want to be unable to change anything the contract does not allow (spacing, system colours, layout internals), so that every Theme stays consistent and accessible.
25. As a Vendor Owner, I want a Preview button that opens my Store with the chosen Theme and unsaved-or-saved settings, so that I can judge it before customers do.
26. As a Vendor Owner, I want Preview never to change what customers see, so that experimenting is safe.
27. As a Vendor Owner, I want a Publish button, so that I decide when the new look goes live.
28. As a Vendor Owner, I want the live Storefront to change immediately after publishing without a rebuild, so that my edit is visible at once.
29. As a Vendor Owner, I want my settings for a Theme remembered if I switch away and back, so that experimenting does not lose work.
30. As a Vendor Owner, I want to be unable to preview or publish for any Store but mine, so that I cannot affect others.
31. As a visitor, I want a Preview address to behave like the normal Store for me, so that sharing a link does not expose unpublished looks.
32. As a visitor, I want a Preview request from a non-owner to show the published Theme, so that nobody can see a draft look.
33. As a visitor, I want the Store to load only the code of its own Theme, so that pages stay fast on a phone.
34. As a visitor, I want every Theme to be right-to-left and mobile-first, so that the Store works for the primary audience.
35. As a visitor, I want visible focus outlines, large tap targets and Arabic labels for assistive technology, so that the Store is accessible.
36. As a visitor, I want the header to show the logo, category navigation, search and cart count, so that I can move around easily.
37. As a visitor, I want the footer to show contact details and social links, so that I can reach the Vendor.
38. As a visitor, I want a friendly Arabic not-found page in the Store's own look, so that a wrong address is not a dead end.
39. As a visitor, I want Home, Category, Product and Search to keep working exactly as in Phase 3 inside the Theme, so that nothing I could do before is lost.
40. As a visitor, I want a Theme that is missing a page to still show that page in the default look, so that no address ever breaks.
41. As a Theme author, I want one written contract describing the manifest, pages, page data, settings schema and registration steps, so that I can build a Theme alone.
42. As a Theme author, I want each page to receive typed data with a declared contract version, so that I know exactly what I can rely on.
43. As a Theme author, I want to declare the contract version my Theme targets, so that the Platform knows whether it is supported.
44. As a Theme author, I want a Theme targeting an unsupported contract major to fall back to the default page rather than crash, so that Stores stay up.
45. As a Theme author, I want minor contract versions to only add data, so that my Theme keeps working when the Platform upgrades.
46. As a Theme author, I want the Platform to support the current and the previous contract major, so that I have time to migrate.
47. As a Theme author, I want to be unable to query the database from a Theme, so that Themes cannot leak another Store's data.
48. As a developer, I want page data built in one place per page with a contract version, so that Themes and controllers agree on shape.
49. As a developer, I want the Phase 3 controllers to return those page data objects, so that the contract is the only shape Themes see.
50. As a developer, I want placeholder Cart, Checkout, Account and Orders pages and data now, so that later phases fill content without changing the contract.
51. As a developer, I want the active Theme for a Store resolved in one place and cached, so that every request is cheap.
52. As a developer, I want that cache cleared whenever a Store's Theme or settings change, so that edits are never stale.
53. As a developer, I want Theme settings turned into style variables by one piece of code, so that every Theme styles itself the same way.
54. As a developer, I want the isolation helper to cover the new Store-owned data, so that the Phase 1 guarantee keeps holding.

## Implementation Decisions

- **Modules touched.** A new Theming module owns the Theme registry, manifest validation, active-Theme resolution, settings validation and style-variable generation, depending only on Core and Tenancy. Storefront depends on Theming and Catalog and owns the page data objects and page resolution. Backoffice gains the Admin Theme resource and the Vendor Appearance page. Dependency direction stays one-way; Theming never reaches upward.
- **Theme folder and manifest.** A Theme is a folder containing a manifest and a Vue page per Storefront page. The manifest declares name, slug, version, targeted contract version, preview image, the pages it supports, and a settings schema. Required pages are Home, Category, Product, Cart, Checkout, Account, Orders and NotFound. Search keeps working through the shared layout and result components from Phase 3 and is treated as part of the Category family of pages for this contract; the contract document states this explicitly.
- **Settings schema.** Field types are colour, select, range and toggle, each with a default. Validation of both the schema (at registration) and stored values (at save) is driven by the same definition.
- **What a Vendor may change.** Only primary and secondary colour, heading and body font from an approved list of three to five self-hosted Arabic fonts, corner style (sharp or soft), and Theme-specific schema fields. Spacing, type scale and system colours are fixed Platform tokens. Contrast is advisory: a warning is shown and readable text colour is chosen automatically, as in Phase 2.
- **Registry.** A Themes table (slug, name, version, active flag, manifest JSON). Registration validates, then upserts, and is idempotent. Two commands: register one Theme by slug, and list Themes. The Admin re-scan action runs the same registration logic for every folder. The default Theme is always active and cannot be deactivated.
- **Store data.** The Store already carries a Theme reference and a Theme Settings JSON; both are used. Settings are stored per Theme inside that JSON so switching away and back keeps earlier choices. Where a setting is unset, the Theme default applies; for the two colours, the Store's identity colours from Phase 2 are the fallback before the Theme default. This precedence is an open assumption (see Further Notes).
- **Active Theme resolution.** One service resolves the Theme a request should use: the Store's chosen Theme if it is registered and active, otherwise the default. The result is cached per Store and cleared on any change to the Store's Theme, its settings, or the Theme's active flag or version.
- **Preview.** A query parameter names a Theme to preview. It is honoured only for an authenticated Vendor Owner of that same Store (Platform Admins are not given it in this phase); for everyone else it is ignored silently and the published Theme renders. Preview never writes to the Store. Preview may also carry unsaved settings from the Appearance page; these are validated like saved ones and never persisted.
- **Contract versioning.** Semantic version. The Platform declares its current contract version and supports the current major and the previous one. A Theme targeting any other major is treated as unsupported and every one of its pages falls back to the default Theme. A Theme missing a single page falls back for that page only. Only the Platform changes the contract; the contract is a code constant, not Vendor input.
- **Page data objects.** One per page (Home, Category, Product, Cart, Checkout, Account, Orders, plus NotFound and Search), each carrying the contract version and containing only plain values: strings, numbers, booleans, lists, and preformatted prices. They are built in Storefront from models, never inside a Theme. The Phase 3 controllers are refactored to return them with their current output unchanged. Cart, Checkout, Account and Orders data are placeholders with the final shape the later phases will fill. The page data shape is the only thing a Theme may rely on.
- **Page resolution.** The Storefront resolves a page name to the active Theme's page when it exists and is supported, otherwise to the default Theme's. Theme code is loaded lazily per Theme so a Store downloads only its own Theme. Shared design tokens are exposed as CSS variables; Themes use variables only, never raw values.
- **Default Theme.** Complete, mobile-first and right-to-left. Layout with header (logo, category navigation, search, cart count) and footer (contact, social). All required pages exist; Cart, Checkout, Account and Orders render their data objects with placeholder content. Accessibility: visible focus rings, 44px minimum tap targets, Arabic accessible labels. The Phase 3 Storefront components move into it.
- **Routes.** Cart, Checkout, Account and Orders have no public routes until their phases; this phase builds only their pages and data so the contract is complete and exercised by tests. NotFound is used for unknown addresses inside a Store.
- **Admin Panel.** A Theme resource lists Themes with version, preview image and active state, with activate/deactivate and the re-scan action. Platform Admins only.
- **Vendor Panel.** An Appearance page, Vendor Owners only and limited to their own Store: a grid of active Themes with preview images, the live Theme marked, a settings form generated from the chosen Theme's schema, Preview and Publish buttons.
- **Contract document.** A written Theme contract for authors is part of the deliverable, since the phase plan lists it.
- **Phase plan wins.** Where the PRD and phase plan differ, the phase plan applies (ADR 0001): additional Themes are Phase 12, PHPUnit replaces Pest wording.

## Testing Decisions

- **What makes a good test.** Assert external behaviour only: given this request, panel interaction, command or actor, what page, props, stored data or visible state results. Do not assert cache keys, which class resolved a Theme, or the internal order of lookups.
- **Seam 1: HTTP and panel boundary (primary).** Feature tests through Storefront routes, the Vendor Appearance page, the Admin Theme resource and the registration commands. Cases:
  - registering a valid Theme makes it appear to Vendors; an invalid manifest is rejected with reasons; registering twice changes nothing; re-scan picks up new and upgraded Themes;
  - the default Theme cannot be deactivated; a Store on a deactivated Theme still renders;
  - a Vendor chooses a Theme, saves settings, publishes, and the live Storefront changes at once; another Store is untouched; settings survive switching Themes;
  - invalid settings (bad colour, option outside list, number out of range, unknown field) are rejected; values outside the Vendor's permitted set cannot be saved;
  - style variables and readable text colours reflect saved settings, with sharp and soft corners;
  - preview works for the owner of that Store, is ignored for guests, other Vendors and customers, never changes the published Theme, and cannot reveal an inactive or unregistered Theme;
  - fallback: a Theme missing a page uses the default page for that page only; a Theme targeting an unsupported contract major uses the default for every page; the previous major is still supported;
  - Phase 3 Storefront behaviour is unchanged after the controller refactor (existing Storefront tests keep passing);
  - every page data object carries the contract version and contains no model objects;
  - Admin-only and Vendor-only access, and cache invalidation shown by behaviour (a change is visible on the next request).
- **Seam 2: pure units.** Manifest and settings-schema validation, and style-variable generation, are value-in/value-out and tested directly. This is the only unit-level seam, justified by the many edge cases in validation.
- **Isolation.** Reuse the Phase 1 isolation helper for any new Store-owned data and add the case that one Store's Theme settings and previews cannot affect another.
- **Front-end gate.** The type check and production build must pass, since pages are now resolved by name from Theme folders.
- **Framework and gate.** PHPUnit, no new test dependency; formatting and tests must pass.
- **Prior art.** Phase 2 identity tests (Vendor page, contrast warning, Storefront tokens) and Phase 3 Storefront page tests are the templates.

## Out of Scope

- Additional Themes beyond `default` (Phase 12) and any page builder or section editor (V2).
- Cart, Checkout, Account and Orders behaviour and routes (Phases 5–7); only their pages and data shapes.
- Custom code, custom CSS or font uploads by Vendors; custom domains.
- Vendor-authored Themes or a Theme marketplace; Theme installation from the Admin Panel (only re-scan of the folder).
- Per-page SEO overrides and Theme-level translations beyond Arabic.
- Changing the contract by anyone other than the Platform team.

## Further Notes

- **Tracker.** As in Phases 1–3, no tracker is configured and the repo has no git remote, so this spec lives in `docs/specs/` and tickets go to `.scratch/phase-4-theme-engine/issues/` with `ready-for-agent`. Run `/setup-matt-pocock-skills` to wire a real tracker.
- **Seams to confirm.** One primary seam (HTTP and panel boundary) plus a small pure-unit seam for manifest/settings validation and style variables, reusing the existing isolation helper. Say so if you want different seams.
- **Suggested ticket order.** Theming module and registry with commands; manifest and settings validation; style-variable generation; page data objects and Phase 3 controller refactor; Theme resolution, fallback and lazy loading; default Theme layout and existing pages; placeholder pages; Preview; Admin Theme resource; Vendor Appearance page; contract document; phase gate.
- **Open assumptions.**
  - Precedence of colours: Theme setting, then the Store identity colour from Phase 2, then the Theme default. The alternative is to move the identity colours entirely into Theme Settings and remove them from the identity page; confirm which you prefer.
  - Platform Admins cannot use Preview in this phase.
  - Search shares the Category family of components rather than being its own required page.
  - The approved font list is three to five fonts with Cairo and IBM Plex Sans Arabic included; the exact list is a design decision for implementation.
  - The PRD calls for three MVP Themes while the phase plan defers extras to Phase 12; the phase plan wins.
