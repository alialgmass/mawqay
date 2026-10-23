# 09: Scope-disable rule is enforced

**What to build:** Looking across all Stores is possible only through one explicit helper, used by the Admin Panel's all-Stores views. An automated check fails whenever code outside Admin Panel code disables the Store scope.

**Blocked by:** 05, 06

**Status:** ready-for-agent

- [ ] The Admin Panel's Store list uses the explicit helper to see all Stores
- [ ] An automated test fails if any code outside Admin Panel code calls the helper or the framework's scope-disabling call
- [ ] The check is demonstrated to fail on a deliberately violating stand-in, then pass on the real code
