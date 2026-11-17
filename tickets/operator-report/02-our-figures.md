# 02. Our figures

- Status: todo
- Blocked by: 01, generation-updates 03
- Spec: docs/features/operator-report.md

## What to build

The report shows, for the chosen date range, our own figures: totals, one row per day with its per-
user rows, and one row per user, grouped by the UTC day each generation completed, with every amount
exact.

## Acceptance criteria

- [ ] Without dates, the report covers the last 30 UTC days ending today.
- [ ] A range longer than 92 days is accepted and shows every day.
- [ ] A start after the end is rejected with its message.
- [ ] Every day in the range has a row, with zeros on days with no generations.
- [ ] A generation counts on the UTC day of its provider `completed_at`, not the day it was created
      (a generation created at 23:59 UTC and completed at 00:01 UTC counts on the second day).
- [ ] Failed generations add nothing to any figure.
- [ ] User spend is the sum of charges, provider cost the sum of recorded provider costs, and profit
      their difference, on every row and in the totals.
- [ ] Per-user rows under a day add up to that day's row; per-user rows for the range add up to the
      totals.
- [ ] Every amount shows 9 decimals.

## Notes
