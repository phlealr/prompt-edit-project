# 0027. Polling is capped and never fails an accepted generation

- Status: accepted
- Date: 2026-11-17

## Context

ADR 0010 adds a scheduled poll every minute for generations whose webhooks may be lost, and leaves its
age threshold to configuration. Every poll is a request against the 60 per minute that Pixelforge
allows per account, shared with submissions (`docs/spec/pixelforge-api.md#rate-limits-and-quotas`).
Only the provider knows whether an accepted generation will be billed, and a generation billed by the
provider but failed by us would never be charged, so our costs would no longer match `GET /v1/usage`
(`docs/spec/pixelforge-api.md#usage`).

## Decision

Each generation records when it was last polled. Each run polls generations in `queued` or
`processing` not updated or polled in the last 2 minutes, at most 10 per run, the least recently
updated or polled first. Generations in `pending` are left to the submission job. A `429` from the
provider ends the run; the rest wait for the next minute. A `503` or a network error skips that one
generation until the next run. The app never fails a
generation the provider has accepted: a generation still not final an hour after it was submitted, or
one the provider answers `404` for, is logged as an error and keeps its hold.

## Rules it creates

- Only `queued` and `processing` generations are polled.
- The age threshold (default 2 minutes) and the per-run cap (default 10) are set in configuration.
- A generation stores when it was last polled, and a run picks the generations least recently
  updated or polled first.
- A `429` while polling ends the run without error; a `503` or network error skips that generation
  and the run continues.
- No code fails a generation after the provider has accepted it; only a provider update makes it
  final.
- A poll answered `404`, or of a generation non-final more than an hour after submission, logs an
  error and changes nothing.

## Consequences

Polling uses at most a sixth of the account's request budget, and a lost webhook is recovered within a
few minutes. A generation the provider truly lost stays non-final with its credits held until someone
looks at the error, which is preferable to releasing credits for something that may yet be billed.

## Alternatives considered

- **Poll every non-final generation every run**: a burst of generations could use up the request
  budget that submissions need.
- **Pick by oldest last update only**: generations the provider lost never change, so they would be
  picked first on every run and, ten of them, block every other generation from being polled.
- **Fail a generation after a timeout**: releases the user's credits, but if the provider later
  completes and bills it, our costs no longer match its usage and the final status cannot record it.

## In plain words

Once a minute we ask Pixelforge about at most ten generations that have gone quiet, taking turns so
none is skipped forever.
We never decide on our own that a generation failed once Pixelforge has it, because only Pixelforge
knows whether it will bill us; a stuck one is logged for us to look at.
