# 03: CI pipeline

**What to build:** Every change is checked automatically: dependencies install, the front end builds, formatting is checked and the tests run. Static analysis is added only if the Larastan dependency is approved; otherwise the spec's gate omits it.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] The pipeline fails when formatting fails
- [ ] The pipeline fails when any test fails
- [ ] The pipeline fails when the front-end build fails
- [ ] Whether static analysis is included is recorded in the ticket outcome, matching the Larastan decision
