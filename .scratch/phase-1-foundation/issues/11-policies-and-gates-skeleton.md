# 11: Policies and gates skeleton

**What to build:** A baseline set of policies and gates exists for Platform Admin, Vendor Owner and Customer, so later phases add rules instead of inventing the mechanism.

**Blocked by:** 07, 08

**Status:** ready-for-agent

- [ ] A gate distinguishes Platform Admin from Vendor Owner from Customer
- [ ] A Vendor Owner is denied actions on another Store's record
- [ ] A Customer is denied Platform and Vendor actions
- [ ] Tests assert the allow and deny outcomes, not the policy classes
