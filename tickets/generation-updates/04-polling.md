# 04. Polling

- Status: todo
- Blocked by: 03
- Spec: docs/features/generation-updates.md

## What to build

Every minute a scheduled job fetches from the provider the generations in `queued` or `processing`
that have gone quiet, at most the configured number, least recently updated or polled first, and
applies what it gets through the one action. Limits and errors never fail a generation the provider
accepted (ADR 0010, ADR 0027).

## Acceptance criteria

- [ ] The polling job is scheduled every minute.
- [ ] It polls only `queued` and `processing` generations not updated or polled within the
      configured age, at most the configured number per run, least recently updated or polled first.
- [ ] A generation polled with no change is not picked again before others that waited longer, so a
      stuck generation cannot block the rest.
- [ ] A generation whose webhooks never arrive reaches the provider's final status, with the same
      records as by webhook.
- [ ] A `429` while polling ends the run without error.
- [ ] A `503` or a network error while polling one generation skips it until the next run, and the
      run continues with the others.
- [ ] A `404`, or a generation not final an hour after submission, logs an error and leaves the
      generation and its hold unchanged.
- [ ] The webhook job, the polling job and the submission job all change a generation only through
      the one action.
- [ ] A `pending` generation that may exist at the provider and whose submission job gave up is
      re-sent with its idempotency key and without the rate check, and the answer is applied through
      the one action (ADR 0042).

## Notes
