# 05. Comparison on the deployed app

- Status: done
- Blocked by: 03, and the first deploy
- Spec: docs/features/operator-report.md

## What to build

A check, not new code: on the deployed app, the report shows no difference with Pixelforge on any
day once all generations are final.

## Acceptance criteria

- [x] On the deployed app, every day's difference is `0.000000000` once all generations are final.

## Notes

Checked 2026-11-18 at 10:01 UTC: every day in the report shows a difference of $0.000000000
(2026-11-18: our cost and Pixelforge's both $0.155045000; spend $0.193806250, profit
$0.038761250). See generation-updates 05 for the generations used.
