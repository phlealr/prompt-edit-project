# 06. Submission job: happy path

- Status: todo
- Blocked by: 05
- Spec: docs/features/generating.md

## What to build

The submission job checks the provider's rates fresh against the generation's stored rates, sends
the generation with its UUID as idempotency key and its id in the metadata, and applies the answer
through the one action that applies provider updates: the generation moves to `queued` and stores
the provider's id. That action moves statuses forward only, so a replayed answer with a later status
and a job that runs twice are both harmless (ADR 0009, ADR 0010, ADR 0025, ADR 0026).

## Acceptance criteria

- [ ] No web request calls the provider to create a generation.
- [ ] The create request carries the generation's UUID as `Idempotency-Key`, the same on every
      attempt, and the generation's id in `metadata`.
- [ ] An accepted create moves the generation to `queued` and stores the provider's id.
- [ ] A create response with a later status (an idempotent replay) is applied through the shared
      action and never moves the generation back.
- [ ] Running the job twice for the same generation creates no second provider generation, hold or
      release.
- [ ] Before each create attempt, the job fetches the provider's rates bypassing the cache, and
      sends only when they equal the stored ones.

## Notes
