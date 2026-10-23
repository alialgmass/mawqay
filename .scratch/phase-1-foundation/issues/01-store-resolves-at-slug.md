# 01: Store resolves at /{store-slug}

**What to build:** A visitor opening a Store's address sees that Store's Storefront shell: its name, in an Arabic, right-to-left, mobile-first layout using the default CSS-variable tokens. An unknown slug shows a not-found page. The five modules (Core, Tenancy, Auth, Storefront, Backoffice) exist with one-way dependencies, a Store can be saved with slug, name, status (Draft, Active, Suspended), optional Theme, Theme Settings, logo, commission rate and contact fields, and the Store is resolved through a swappable resolver.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Opening `/{slug}` for an existing Store shows that Store's name
- [ ] An unknown slug returns a 404 page
- [ ] The page is right-to-left, `lang=ar`, and renders the default token set
- [ ] The resolver can be replaced without changing other code (verified by a test using a stand-in resolver)
- [ ] The five modules are registered and depend only in the allowed direction
