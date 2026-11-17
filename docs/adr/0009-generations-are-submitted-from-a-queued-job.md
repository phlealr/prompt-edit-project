# 0009. Generations are submitted to the provider from a queued job, never in the web request

- Status: accepted
- Date: 2026-11-17

## Context

Pixelforge allows 3 generations queued or processing at once **per account**, shared by all our
users, and rejects excess with `429 concurrency_limit_exceeded`; it also returns `429` for the
per-minute limit and `503 overloaded` at random. Clients must respect `Retry-After` and cap their
retries (`docs/spec/pixelforge-api.md#rate-limits-and-quotas`). Retrying a create safely needs an
`Idempotency-Key` (`docs/spec/pixelforge-api.md#idempotency`), and a webhook can arrive before the
create request returns (`docs/spec/pixelforge-api.md#delivery-is-at-least-once-in-any-order`).

## Decision

Submitting creates the local generation in `pending`, places the hold (ADR 0006), dispatches a job,
and returns immediately. The job sends the generation to the provider with an idempotency key equal
to the generation's UUID and the generation's id in the provider's metadata. On a rate limit or
overload, the job waits the `Retry-After` seconds and tries again, up to a capped number of
attempts. A rejected prompt fails the generation with `prompt_rejected`; exhausting the retries fails
it with `provider_unavailable`. Both release the hold.

## Rules it creates

- No web request calls the provider to create a generation.
- Every create request sends the generation's UUID as the idempotency key, the same on every retry.
- Every create request sends the generation's id in the provider metadata.
- Retries wait at least the provider's `Retry-After`, and the number of attempts is capped in
  configuration.
- Creating the generation, placing its hold and dispatching its job happen in one transaction.

## Consequences

Account-wide rate and concurrency limits are absorbed by waiting instead of becoming errors on screen,
and a retried create can never produce a second generation or a second bill. A rejected prompt shows
up as a failed generation a few seconds later instead of an instant form error.

## Alternatives considered

- **Call the provider inside the request**: a rejected prompt is reported instantly, but every 429 or
  503 becomes an error or a long wait for the user, and the concurrency limit is routine with several
  users.

## In plain words

The submit button only saves the generation and queues a job; the job talks to Pixelforge and waits
politely on rate limits. Our generation id is the idempotency key, so retries can never create or bill
a second image.
