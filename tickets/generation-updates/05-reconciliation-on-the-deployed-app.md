# 05. Reconciliation on the deployed app

- Status: done
- Blocked by: 04, and the first deploy
- Spec: docs/features/generation-updates.md

## What to build

A check, not new code: with generations left on the deployed app, including `[fail]`, `[blocked]`,
`[duplicate]` and `[slow]` ones, our provider costs per UTC completion day equal what Pixelforge
reports for the deployed app's key. Any difference is a bug to fix before the hand-in.

## Acceptance criteria

- [x] For the generations left on the deployed app, the sum of provider costs per UTC completion day
      equals `cost_usd` from `GET /v1/usage` for that day.

## Notes

Checked 2026-11-18 at 10:01 UTC on the deployed app, after `[succeed]`, `[duplicate]`, `[slow]`,
`[fail]`, `[blocked]` and `[overloaded]` generations. For 2026-11-18: 4 generations completed,
provider cost $0.155045000, Pixelforge `cost_usd` $0.155045000. The `[duplicate]` generation was
charged once. Before the scheduler was turned on, one succeeded generation was missing ($0.066630
short); the minute polling found it as soon as the scheduler ran.
