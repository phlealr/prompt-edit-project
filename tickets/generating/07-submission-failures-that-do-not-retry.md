# 07. Submission failures that do not retry

- Status: done
- Blocked by: 06
- Spec: docs/features/generating.md

## What to build

The submission job fails a generation at once, releasing its hold, when retrying cannot help: the
prompt is rejected (`prompt_rejected`), the rates changed (`price_changed`), or the provider answers
with the daily limit, an authentication, validation or idempotency error (`provider_unavailable`,
logged as an error). Each failure reason carries its user-facing message (ADR 0022, ADR 0025, ADR
0026).

## Acceptance criteria

- [x] Before each create attempt, the job fetches the provider's rates bypassing the cache; when any
      rate differs from the stored ones, nothing is sent, the generation fails with `price_changed`,
      and it gets one `release` equal to its hold and no `charge`.
- [x] `[blocked]`: the generation fails with `prompt_rejected` after one attempt, gets one `release`
      equal to its hold and no `charge`, and the user's balance equals the balance before
      submitting.
- [x] `429 daily_limit_exceeded`, `401`, `422` and `409` fail the generation with
      `provider_unavailable` after one attempt, release its hold, and log an error.
- [x] Each failure reason shows its message from the table in the spec.
- [x] `failure_reason` is set if and only if the status is `failed`.

## Notes

- Every failure goes through `ApplyProviderUpdate`, the one action that changes a status: a failure
  decided before the provider created anything is a `ProviderUpdate` with no provider id
  (`failedBeforeCreation`), and it only applies to a `pending` generation, so a second run of the job
  cannot fail a generation the first run already sent.
- `ProviderUpdate` refuses a failure reason without the failed status and the reverse (ADR 0025).
- A failure releases exactly the negated hold, inside the generation's lock; the ledger's unique
  (generation, type) rows stop a second release.
- `PromptRejected` and `SubmissionRefused` are our exceptions; a refused submission's error log and
  stored response carry Pixelforge's status, code and raw body.
- A create replay that comes back `failed` maps to `provider_failed`.
- The messages are on `FailureReason::message()`; the history cards show them (history 02).
