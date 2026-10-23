# 07: Customer guard with Store-bound sessions

**What to build:** A separate customer sign-in mechanism exists with no screens yet. A Customer's session from Store A is not valid in Store B, and login and registration are throttled.

**Blocked by:** 05

**Status:** ready-for-agent

- [ ] A Customer authenticated in Store A is treated as signed out in Store B
- [ ] Customer authentication is independent of the Platform Admin and Vendor Owner sign-in
- [ ] Repeated login or registration attempts are throttled
- [ ] Tests cover the cross-Store session case through real requests
