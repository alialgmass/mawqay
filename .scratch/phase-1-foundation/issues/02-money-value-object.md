# 02: Money value object

**What to build:** Amounts are held as whole piasters and formatted for EGP, so no later phase touches floating-point money.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Money is created from and read back as integer piasters
- [ ] EGP formatting shows amounts consistently, e.g. 126000 piasters renders as 1,260 EGP
- [ ] Arithmetic and comparison never produce fractional piasters
- [ ] Behaviour tests cover construction, formatting, addition, subtraction and rejection of non-integer input
