# 0042. A generation that may exist at the provider is never failed for running out of attempts

- Status: accepted
- Date: 2026-11-18
- Amends: ADR 0022, ADR 0027

## Context

The submission job gives up after the configured attempts and fails the generation with
`provider_unavailable` (ADR 0022). If an earlier attempt ended in a network error, timeout or server
error, the provider may have created the generation (ADR 0040) and will bill it; failing it here
releases the hold, and the later webhooks are ignored because the status is final (ADR 0025), so we
pay and the user is never charged. Such a generation has no provider id, so it cannot be fetched;
but re-sending the create with the same idempotency key returns it as it is now, without counting
against the provider's limits (`docs/spec/pixelforge-api.md#idempotency`).

## Decision

When the attempts run out and the generation may exist at the provider, the job leaves it `pending`
with its hold and logs an error. The scheduled polling job (ADR 0027) re-sends these generations with
their idempotency key, which either returns the generation the provider created or creates it.
Only a generation that certainly does not exist at the provider is failed with `provider_unavailable`.

## Rules it creates

- Running out of attempts fails a generation with `provider_unavailable` only when it may not exist
  at the provider; otherwise it stays `pending` with its hold, and an error is logged.
- Polling also re-sends `pending` generations that may exist at the provider and whose submission
  job has given up, with the same idempotency key and without the rate check.

## Consequences

The provider never bills a generation we released. A generation can stay `pending` longer, holding
the user's credits, until a re-send gets an answer.

## Alternatives considered

- **Accept the risk and fail it**: no extra code, but a rare generation is paid to the provider and
  never charged.
- **Re-dispatch the job once more after a long delay**: bounded, but stretches the attempt limit and
  still ends in the same choice.

## In plain words

If Pixelforge might already be making the image, we never give up on it: polling asks again with the
same key until Pixelforge tells us what happened. Only a generation we know Pixelforge never got is
failed for free.
