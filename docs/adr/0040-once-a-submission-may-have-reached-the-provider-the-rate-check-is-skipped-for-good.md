# 0040. Once a submission may have reached the provider, the rate check is skipped for good

- Status: accepted
- Date: 2026-11-18

## Context

Before each create attempt the job compares fresh rates with the generation's stored rates and fails
it with `price_changed` on any difference (ADR 0026). The check is skipped after an attempt that
ended in a network error or timeout, because the provider may already have created the generation.
A later attempt answered `503 overloaded` creates nothing itself, but the earlier timed-out request
may still have created the generation (`docs/spec/pixelforge-api.md#idempotency`). A re-queued job
keeps no state of its own between attempts.

## Decision

The first network error or timeout on a create attempt sets a flag on the generation. While it is set,
every later attempt is sent without the rate check.

## Rules it creates

- A generation records that a create attempt may have reached the provider (a network error or
  timeout), and the flag is never cleared.
- No attempt after that is preceded by a rate check, so such a generation never fails with
  `price_changed`.

## Consequences

A generation the provider may have created is never failed by us while the provider bills it; it is
charged at cost plus 25% whatever its rates (ADR 0026). One boolean column more on generations.

## Alternatives considered

- **Skip the check on the next attempt only**: the literal reading of ADR 0026, but an attempt after
  that could fail a generation the provider had already created and bills.

## In plain words

If a request might have reached Pixelforge, we never again refuse the generation over a price change,
because Pixelforge may already be making it. We remember that on the generation, since a retried job
forgets everything.
