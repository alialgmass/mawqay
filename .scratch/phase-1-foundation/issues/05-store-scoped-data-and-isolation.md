# 05: Store-owned data is scoped and isolated

**What to build:** A Customer record belongs to exactly one Store, is automatically filtered to the current Store and is assigned its Store on creation. The same email or phone registered in two Stores gives two independent Customers. A reusable isolation test helper proves Store A cannot read, list, count or modify Store B's rows, and later phases extend it.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] A Customer created while in Store A belongs to Store A without setting it manually
- [ ] Queries in Store A never return Store B's Customers
- [ ] The same email in two Stores creates two separate Customers with separate passwords
- [ ] The isolation helper asserts read, list, count and modify separation using the Customer record
- [ ] A later model can be added to the helper in a few lines (shown by a second probe case in the test)
