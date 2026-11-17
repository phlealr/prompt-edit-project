# 07. Submission failures that do not retry

- Status: todo
- Blocked by: 06
- Spec: docs/features/generating.md

## What to build

The submission job fails a generation at once, releasing its hold, when retrying cannot help: the
prompt is rejected (`prompt_rejected`), the rates changed (`price_changed`), or the provider answers
with the daily limit, an authentication, validation or idempotency error (`provider_unavailable`,
logged as an error). Each failure reason carries its user-facing message (ADR 0022, ADR 0025, ADR
0026).

## Acceptance criteria

- [ ] Before each create attempt, the job fetches the provider's rates bypassing the cache; when any
      rate differs from the stored ones, nothing is sent, the generation fails with `price_changed`,
      and it gets one `release` equal to its hold and no `charge`.
- [ ] `[blocked]`: the generation fails with `prompt_rejected` after one attempt, gets one `release`
      equal to its hold and no `charge`, and the user's balance equals the balance before
      submitting.
- [ ] `429 daily_limit_exceeded`, `401`, `422` and `409` fail the generation with
      `provider_unavailable` after one attempt, release its hold, and log an error.
- [ ] Each failure reason shows its message from the table in the spec.
- [ ] `failure_reason` is set if and only if the status is `failed`.

## Notes
