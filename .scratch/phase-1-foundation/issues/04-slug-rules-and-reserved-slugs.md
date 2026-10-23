# 04: Slug rules and Reserved Slugs

**What to build:** A Store Slug must be 3 to 30 characters of lowercase letters, digits and hyphens, unique across the Platform, and not on the configurable Reserved Slug list (`admin`, `vendor`, `api`, `login`, `register`, `logout`, `storage`, `assets`, `build`, `up`, `livewire`, `filament`).

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] Slugs shorter than 3 or longer than 30 characters are rejected
- [ ] Characters outside `a-z`, `0-9` and hyphen are rejected
- [ ] A duplicate slug is rejected
- [ ] Every Reserved Slug is rejected, covered by one automated test over the whole list
- [ ] A reserved slug's URL never opens a Storefront and returns 404
- [ ] The Reserved Slug list is changed in configuration, not code
