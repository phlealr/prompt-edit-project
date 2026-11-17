# 04. Uncovered charges

- Status: todo
- Blocked by: 02
- Spec: docs/features/operator-report.md

## What to build

The report lists the charges that went above their hold in the range, and the users whose balance is
below zero now, so uncollected money is visible (ADR 0026).

## Acceptance criteria

- [ ] Generations in the range whose charge exceeds their hold are listed with user, day, hold,
      charge and uncovered amount, and the total appears in the totals.
- [ ] Users with a balance below zero are listed with their balance.

## Notes
