# 06. Submission job: happy path

- Status: done
- Blocked by: 05
- Spec: docs/features/generating.md

## What to build

The submission job checks the provider's rates fresh against the generation's stored rates, sends
the generation with its UUID as idempotency key and its id in the metadata, and applies the answer
through the one action that applies provider updates: the generation moves to `queued` and stores
the provider's id. That action moves statuses forward only, so a replayed answer with a later status
and a job that runs twice are both harmless (ADR 0009, ADR 0010, ADR 0025, ADR 0026).

## Acceptance criteria

- [x] No web request calls the provider to create a generation.
- [x] The create request carries the generation's UUID as `Idempotency-Key`, the same on every
      attempt, and the generation's id in `metadata`.
- [x] An accepted create moves the generation to `queued` and stores the provider's id.
- [x] A create response with a later status (an idempotent replay) is applied through the shared
      action and never moves the generation back.
- [x] Running the job twice for the same generation creates no second provider generation, hold or
      release.
- [x] Before each create attempt, the job fetches the provider's rates bypassing the cache, and
      sends only when they equal the stored ones.

## Notes

- `ApplyProviderUpdate` is the one action that changes a generation from a provider update. It locks
  the generation, moves it forward only, and stores the provider's id and raw response body. Until
  `generation-updates` 02/03 settle holds, it ignores `succeeded` and `failed`, so a later webhook or
  poll can still settle them.
- The rate check compares `Rates` by amount (ADR 0035): "5" and "5.00" are the same rate.
- Decision: arrays are never used as objects (ADR 0041). This ticket introduced `app/Data/<Area>/`
  and replaced the earlier array shapes: `EstimateForUser`, `GenerationSummary`, Pixelforge's
  `Pricing` with `OutputTokens` and `ReasoningTokens`, the raw response kept as a string, and
  `defaultSize`/`defaultQuality` props.
- A rate change before sending currently leaves the generation `pending` with its hold; ticket 07
  fails it with `price_changed`.
- An unknown status in a Pixelforge response throws `UnexpectedValueException`; ticket 07 maps
  provider errors to our exceptions.
