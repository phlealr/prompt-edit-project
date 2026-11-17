# 04. Ledger and balance on Generate

- Status: todo
- Blocked by: 03
- Spec: docs/features/generating.md

## What to build

The ledger exists: credit movements are inserted rows, one per (generation, type) at most, and a
user's balance is their sum (ADR 0006). The generate page shows the balance, and when the estimate's
maximum is above it, shows "Not enough credits: this can cost up to $X and your balance is $Y" and
disables Generate. Tests create `purchase` rows directly; buying comes with the `balances` feature.

## Acceptance criteria

- [ ] The generate page shows the user's balance with 4 decimals and the exact amount on hover.
- [ ] When the balance is below the estimate's maximum, Generate is disabled and the page shows "Not
      enough credits: this can cost up to $X and your balance is $Y".
- [ ] Ledger rows can only be inserted; nothing updates or deletes them.
- [ ] The database refuses a second row of the same type for the same generation.

## Notes
