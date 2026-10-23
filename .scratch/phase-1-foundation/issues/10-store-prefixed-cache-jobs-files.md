# 10: Store-prefixed cache, jobs and files

**What to build:** Isolation extends beyond the database: cache keys carry the Store prefix, queued jobs carry their Store explicitly, and uploaded files live in a per-Store folder.

**Blocked by:** 05

**Status:** ready-for-agent

- [ ] A cached value written for Store A is not readable under Store B
- [ ] A queued job runs scoped to the Store it was dispatched for, even when run by a worker with no current Store
- [ ] Files saved for Store A are stored under that Store's folder and not reachable via Store B's path
- [ ] Tests cover each of the three through observable behaviour
