# 08. Retries

- Status: todo
- Blocked by: 07
- Spec: docs/features/generating.md

## What to build

The submission job waits out rate limits, concurrency limits, overloads and network errors: it
retries with the same idempotency key after the longer of `Retry-After` and its backoff (5 s,
doubling, at most 60 s), up to the configured number of attempts, then fails the generation with
`provider_unavailable` and releases its hold. After a network error or timeout the next attempt
skips the rate check, because the provider may already have created the generation (ADR 0022, ADR
0026).

## Acceptance criteria

- [ ] `429 rate_limit_exceeded`, `429 concurrency_limit_exceeded`, `503 overloaded` and a network
      error are retried after the longer of `Retry-After` and the attempt's backoff, with the
      generation staying `pending`.
- [ ] A retry that is then accepted leaves one generation and one hold.
- [ ] `[overloaded]`: after the configured number of attempts (default 8) the generation fails with
      `provider_unavailable` and gets one `release` equal to its hold.
- [ ] After an attempt that ended in a network error or timeout, the next attempt is sent without
      the rate check.
- [ ] The maximum number of attempts and the backoff cap come from configuration.

## Notes
