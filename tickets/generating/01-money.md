# 01. Money

- Status: todo
- Blocked by: none
- Spec: docs/features/generating.md

## What to build

An exact money type that every later ticket uses. It holds an integer number of nanodollars, reads a
provider's decimal dollar string exactly (never through a float), computes a charge as the provider
cost × 1.25 rounded up to a whole nanodollar, and formats an amount for users (4 decimals) and for
operators (all 9 decimals). Shown working by unit tests (ADR 0005, ADR 0007).

## Acceptance criteria

- [ ] `"0.373875000"` reads as 373,875,000 nanodollars, and `"5.00"` as 5,000,000,000; no float is
      used anywhere in the conversion.
- [ ] The charge for a provider cost of 373,875,000 nanodollars is 467,343,750 nanodollars.
- [ ] The charge for a provider cost of 1 nanodollar is 2 nanodollars (1.25 rounded up), and for 4
      nanodollars is 5.
- [ ] 467,343,750 nanodollars formats as `$0.4673` for users and `$0.467343750` for operators.
- [ ] Amounts below zero format with a minus sign in both formats.

## Notes
