# 0032. Provider pricing is cached for five minutes by default

- Status: accepted
- Date: 2026-11-17

## Context

The estimate uses the provider's current pricing, cached for "a few minutes" with the lifetime set in
configuration (ADR 0011). Pixelforge rates can change at any time
(`docs/spec/pixelforge-api.md#pricing-and-token-usage`). The submission job checks the rates fresh,
skipping the cache, before sending (ADR 0026), so a stale cache can never cause a wrong charge.

## Decision

The pricing cache lifetime defaults to 300 seconds, set in configuration and overridable from `.env`.

## Rules it creates

- The pricing cache lifetime is read from configuration, with a default of 300 seconds.

## Consequences

The estimate endpoint calls `GET /v1/pricing` at most once every 5 minutes. After a rate change, an
estimate can be stale for up to 5 minutes; a generation submitted in that window fails with
`price_changed` and is not charged.

## Alternatives considered

- **1 minute**: fresher estimates, more pricing calls.
- **15 minutes**: fewer calls, a longer window of stale estimates and `price_changed` failures.

## In plain words

We remember Pixelforge's prices for 5 minutes. Before sending, the job checks the prices fresh
anyway, so a stale price can only stop a generation, never charge it wrongly.
