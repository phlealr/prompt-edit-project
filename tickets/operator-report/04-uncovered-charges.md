# 04. Uncovered charges

- Status: done
- Blocked by: 02
- Spec: docs/features/operator-report.md

## What to build

The report lists the charges that went above their hold in the range, and the users whose balance is
below zero now, so uncollected money is visible (ADR 0026).

## Acceptance criteria

- [x] Generations in the range whose charge exceeds their hold are listed with user, day, hold,
      charge and uncovered amount, and the total appears in the totals.
- [x] Users with a balance below zero are listed with their balance.

## Notes

- Uncovered charges come from the ledger rows alone: each generation's hold and charge (eager-loaded
  relations); uncovered = charge − hold when positive; their sum is in the totals. Users below zero now
  come from one grouped query over the whole ledger (standard SQL `sum`/`having`).
- Manual check on the local app, 2026-11-18: the section listed user, completion day, hold, charge
  and uncovered amount, and the totals showed their sum.
