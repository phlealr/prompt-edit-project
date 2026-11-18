# 08. Retries

- Status: done
- Blocked by: 07
- Spec: docs/features/generating.md

## What to build

The submission job waits out rate limits, concurrency limits, overloads and network errors: it
retries with the same idempotency key after the longer of `Retry-After` and its backoff (5 s,
doubling, at most 60 s), up to the configured number of attempts, then fails the generation with
`provider_unavailable` and releases its hold. After a network error or timeout every later attempt
skips the rate check, because the provider may already have created the generation (ADR 0022, ADR
0026, ADR 0040).

## Acceptance criteria

- [x] `429 rate_limit_exceeded`, `429 concurrency_limit_exceeded`, `503 overloaded` and a network
      error are retried after the longer of `Retry-After` and the attempt's backoff, with the
      generation staying `pending`.
- [x] A retry that is then accepted leaves one generation and one hold.
- [x] `[overloaded]`: after the configured number of attempts (default 8) the generation fails with
      `provider_unavailable` and gets one `release` equal to its hold.
- [x] After an attempt that ended in a network error or timeout, every later attempt is sent without
      the rate check (ADR 0040).
- [x] The maximum number of attempts and the backoff cap come from configuration.

## Notes

- Decisions: every attempt after a network error or timeout skips the rate check, recorded on the
  generation as `may_exist_at_provider` (ADR 0040); out of attempts, such a generation stays `pending`
  with its hold for polling to re-send (ADR 0042, added to generation-updates 04); a server error is
  retried like a network error, and pricing unavailable for the rate check is retried with backoff
  (ADR 0043). ADR 0022, 0026 and 0027 are marked as amended.
- `tries()` is a method because attributes cannot read configuration; `app/Jobs/CLAUDE.md` says so.
- Verified against the queue code: attempts 1–8 send; attempt 8's release is picked up as attempt 9,
  which exceeds `maxTries` and runs `failed()`. The tests prove `tries()`, each release delay and
  `failed()` separately rather than through a worker.
