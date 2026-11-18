# Jobs

Queued entry points: no business logic here (ADR 0013, ADR 0020). Shared rules:
[`CODING_STANDARDS.md`](../../CODING_STANDARDS.md).

## Shape

- `Jobs/<Area>/<Name>Job.php`; `handle()` calls one action.
- Retry policy is declared with attributes: `#[Tries]`, `#[Backoff]`, `#[Timeout]`. Where an ADR says
  the number is "set in configuration", attributes cannot read it, so the job uses a `tries()` method
  and its own `release()` delay instead (ADR 0009, ADR 0010, ADR 0022).
- A job carries ids and scalars, not whole provider payloads it could fetch or read again.

## Retrying on the provider's limits

The provider's `Retry-After` is honoured (ADR 0009, ADR 0022,
`docs/spec/pixelforge-api.md#rate-limits-and-quotas`):

- When the action throws a busy (rate limited, overloaded) or unreachable (network error, timeout,
  server error) exception, or pricing is unavailable for the rate check, the job releases itself
  back to the queue after the longer of the exception's retry-after seconds and the backoff for that
  attempt (5 seconds, doubling, capped in configuration). This is queue mechanics, not a business
  decision; the action decided it was retryable by throwing that exception.
- Nothing else is retried: the action fails the generation itself for errors waiting cannot fix
  (ADR 0022).
- The maximum number of attempts comes from configuration (default 8).
- When the attempts run out, the job's `failed()` calls the action that fails the generation with
  `provider_unavailable`, which releases its hold, unless the generation may exist at the provider:
  then it stays `pending` with its hold for polling to re-send (ADR 0042, ADR 0043).

## Safety

- A job may run more than once (retries, worker restarts). That is safe because the actions it calls
  are repeat-safe (ADR 0006, ADR 0025); a job adds no state of its own.
- A job created together with a generation is dispatched inside that generation's transaction, so
  both are saved or neither is (ADR 0004, ADR 0009).
- Jobs are dispatched only through Laravel's queue API (ADR 0004).
