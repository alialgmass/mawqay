# 06: Platform Admin reaches the Admin Panel

**What to build:** Users gain a role (Platform Admin or Vendor Owner) and an optional Store. Only a Platform Admin can open the Admin Panel at `/admin`, which is Arabic and right-to-left. Tenancy contributes a read-only Store list and view to the panel through the module-plugin pattern, proving later modules can do the same.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] A Platform Admin signs in and reaches `/admin`
- [ ] A Vendor Owner and an unauthenticated visitor are refused at `/admin`
- [ ] The panel renders in Arabic, right-to-left
- [ ] The Store list and a Store's view page show all Stores, read-only, with no create, edit or delete
- [ ] The Store resource is registered through a module plugin, not directly in the panel configuration
