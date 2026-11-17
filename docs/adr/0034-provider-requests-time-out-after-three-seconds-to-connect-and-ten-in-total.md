# 0034. Provider requests time out after 3 seconds to connect and 10 seconds in total

- Status: accepted
- Date: 2026-11-17

## Context

Laravel's HTTP client waits 30 seconds by default. The estimate route calls the provider's pricing
endpoint whenever its cache is empty, on every debounced keystroke (ADR 0033), so a slow provider
would hold each estimate for up to 30 seconds before the form can show "Price unavailable right now".
Submission requests that time out are retried (ADR 0022).

## Decision

Every request to the provider has a connect timeout of 3 seconds and a total timeout of 10 seconds,
both set in configuration under `services.pixelforge`.

## Rules it creates

- Provider requests use the connect timeout (default 3 s) and total timeout (default 10 s) from
  configuration.

## Consequences

A slow or unreachable provider turns into "Price unavailable right now" within 10 seconds. A response
slower than 10 seconds counts as a failure: for a submission that means a retry, which the
idempotency key makes safe.

## Alternatives considered

- **Laravel's default (30 s)**: nothing to configure, but the estimate can freeze for 30 seconds.
- **2 s / 5 s**: fails faster, but turns slow-but-working answers into failures more often.

## In plain words

We give Pixelforge 3 seconds to answer the phone and 10 seconds to finish. Past that, the form says the
price is unavailable, and a submission is simply retried.
