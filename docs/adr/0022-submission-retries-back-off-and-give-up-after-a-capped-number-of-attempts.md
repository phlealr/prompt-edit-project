# 0022. Submission retries back off and give up after a capped number of attempts

- Status: accepted (amended by ADR 0042 and ADR 0043)
- Date: 2026-11-17

## Context

ADR 0009 submits generations from a queued job that waits on rate limits and overloads, with the
number of attempts capped in configuration, but leaves the numbers and the error-by-error handling
open. Pixelforge asks clients to respect `Retry-After`, back off further on each retry and give up
after a few attempts (`docs/spec/pixelforge-api.md#rate-limits-and-quotas`). Its concurrency limit is
3 generations per account, shared by every user, and a `[slow]` generation processes for up to 90
seconds (`docs/spec/pixelforge-api.md#testing`). Retrying a create is safe because the idempotency key
never changes (`docs/spec/pixelforge-api.md#idempotency`).

## Decision

The submission job retries `429 rate_limit_exceeded`, `429 concurrency_limit_exceeded`,
`503 overloaded`, and network errors or timeouts. Before each retry it waits the longer of the
provider's `Retry-After` and an exponential backoff of 5, 10, 20, 40 seconds, capped at 60 seconds per
wait. It makes at most 8 attempts (about 5 minutes), then fails the generation with
`provider_unavailable`. `429 daily_limit_exceeded` and `401`, `422` and `409` responses are not
retried: the generation fails at once with `provider_unavailable`, and the response is logged as an
error. `400 content_policy_violation` fails the generation with `prompt_rejected`.

## Rules it creates

- Only rate limits, concurrency limits, overloads, and network errors or timeouts are retried.
- Each wait is at least the provider's `Retry-After` and at least the backoff for that attempt
  (5 seconds, doubling, at most 60 seconds).
- The maximum number of attempts (default 8) and the backoff cap (default 60 seconds) are set in
  configuration.
- Running out of attempts fails the generation with `provider_unavailable` and releases its hold.
- A daily limit, an authentication error, a validation error or an idempotency conflict fails the
  generation at once with `provider_unavailable`, releases its hold, and is logged as an error.
- A content policy rejection fails the generation at once with `prompt_rejected` and releases its
  hold.

## Consequences

A burst of users or a few slow generations is absorbed by waiting for up to about 5 minutes, without
hammering Pixelforge. A generation can sit in `pending` for that long before the user learns it
failed. Errors that waiting cannot fix (the daily quota, our own bugs) fail fast instead of wasting
attempts, and the log points at them.

## Alternatives considered

- **Retry every error**: a bad API key or a malformed request would be retried for minutes and still
  fail.
- **Wait only `Retry-After`**: the concurrency limit always answers 5 seconds, so 8 attempts would
  give up after about 40 seconds, sooner than a `[slow]` generation frees its slot.
- **Retry the daily limit until the next UTC day**: the user would wait for hours with credits held.

## In plain words

The job retries only the errors that waiting can fix, and waits a bit longer each time, never less
than Pixelforge asks. After 8 tries, about five minutes, it gives up, marks the generation failed and
gives the credits back. Anything that waiting can't fix fails straight away.
