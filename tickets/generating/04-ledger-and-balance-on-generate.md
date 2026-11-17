# 04. Ledger and balance on Generate

- Status: done
- Blocked by: 03
- Spec: docs/features/generating.md

## What to build

The ledger exists: credit movements are inserted rows, one per (generation, type) at most, and a
user's balance is their sum (ADR 0006). The generate page shows the balance, and when the estimate's
maximum is above it, shows "Not enough credits: this can cost up to $X and your balance is $Y" and
disables Generate. Tests create `purchase` rows directly; buying comes with the `balances` feature.

## Acceptance criteria

- [x] The generate page shows the user's balance with 4 decimals and the exact amount on hover.
- [x] When the balance is below the estimate's maximum, Generate is disabled and the page shows "Not
      enough credits: this can cost up to $X and your balance is $Y".
- [x] Ledger rows can only be inserted; nothing updates or deletes them.
- [x] The database refuses a second row of the same type for the same generation.

## Notes

- Decisions: the ledger model refuses updates and deletes (ADR 0036); accounts cannot be deleted, so
  the starter kit's "Delete account" is removed (ADR 0037); hold, release and charge rows always carry
  their generation, enforced by the actions, not a `CHECK` (ADR 0038).
- `credit_transactions.generation_id` has no foreign key yet: the generations table arrives with
  ticket 05, which adds it.
- The estimate route also returns the balance and `enoughCredits`, so the page never compares
  amounts itself.
- Manual check on the local app, 2026-11-18: with a $0.0000 balance the page shows "Not enough
  credits: this can cost up to $0.0863 and your balance is $0.0000" and Generate is disabled; after a
  $1 purchase it shows "Balance $1.0000" and Generate is enabled; amounts show the exact value on
  hover.
- The buy form under the message comes with `balances` 03.
