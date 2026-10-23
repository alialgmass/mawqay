# 08: Vendor Owner reaches the Vendor Panel, confined to their Store

**What to build:** A Vendor Owner signs in at `/vendor`, sees only their own Store, and has no Store switcher because each Vendor Owner belongs to exactly one Store. Another Store's panel address is refused and the Vendor Owner cannot open the Admin Panel. The panel is Arabic and right-to-left, and the automatic Store scope applies in addition to the panel's tenancy.

**Blocked by:** 05, 06

**Status:** ready-for-agent

- [ ] A Vendor Owner signs in and lands in their own Store's panel
- [ ] The Store switcher is not shown
- [ ] Opening another Store's panel address is refused (403 or 404)
- [ ] A Vendor Owner is refused at `/admin`, and a Platform Admin is refused at `/vendor`
- [ ] Records visible in the panel are limited to the Vendor Owner's Store even if the panel tenancy were bypassed
- [ ] The panel renders in Arabic, right-to-left
