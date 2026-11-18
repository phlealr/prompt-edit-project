# 02. Our figures

- Status: done
- Blocked by: 01, generation-updates 03
- Spec: docs/features/operator-report.md

## What to build

The report shows, for the chosen date range, our own figures: totals, one row per day with its per-
user rows, and one row per user, grouped by the UTC day each generation completed, with every amount
exact.

## Acceptance criteria

- [x] Without dates, the report covers the last 30 UTC days ending today.
- [x] A range longer than 92 days is accepted and shows every day.
- [x] A start after the end is rejected with its message.
- [x] Every day in the range has a row, with zeros on days with no generations.
- [x] A generation counts on the UTC day of its provider `completed_at`, not the day it was created
      (a generation created at 23:59 UTC and completed at 00:01 UTC counts on the second day).
- [x] Failed generations add nothing to any figure.
- [x] User spend is the sum of charges, provider cost the sum of recorded provider costs, and profit
      their difference, on every row and in the totals.
- [x] Per-user rows under a day add up to that day's row; per-user rows for the range add up to the
      totals.
- [x] Every amount shows 9 decimals.

## Notes

- `BuildOperatorReport` returns a typed `OperatorReport` (totals, every day newest first with its
  per-user rows, per user highest spend first), from succeeded generations by the UTC day of the
  provider's `completed_at` ([start 00:00, end + 1 00:00)), grouped once by day.
- The request fills missing dates (end today in UTC, start 29 days before) before validating, so any
  start after the end, including a lone future start, gets "The start date must be on or before the
  end date.".
- Manual checks on the local app, 2026-11-18: the report showed 30 days with 9-decimal amounts;
  clicking a day opened its per-user rows; a start after the end showed the message and kept the
  previous report and the typed dates (`preserveState`).
