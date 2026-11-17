# 05. Reconciliation on the deployed app

- Status: todo
- Blocked by: 04, and the first deploy
- Spec: docs/features/generation-updates.md

## What to build

A check, not new code: with generations left on the deployed app, including `[fail]`, `[blocked]`,
`[duplicate]` and `[slow]` ones, our provider costs per UTC completion day equal what Pixelforge
reports for the deployed app's key. Any difference is a bug to fix before the hand-in.

## Acceptance criteria

- [ ] For the generations left on the deployed app, the sum of provider costs per UTC completion day
      equals `cost_usd` from `GET /v1/usage` for that day.

## Notes
