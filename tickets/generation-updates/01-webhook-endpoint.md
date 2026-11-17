# 01. Webhook endpoint

- Status: todo
- Blocked by: generating 06
- Spec: docs/features/generation-updates.md

## What to build

Pixelforge can post webhooks to the app. The endpoint checks the signature on the raw body and the
timestamp, records each event once by its id, dispatches a job to apply it, and answers `200`; a
duplicate is answered `200` and not dispatched again, a `ping` is recorded and applies nothing (ADR
0010). The job only needs to exist here; applying events comes next.

## Acceptance criteria

- [ ] A request with no signature, a wrong signature, a signature over a different body, or a
      timestamp more than 5 minutes off gets `400`, and no event, job or change is recorded.
- [ ] The signature is checked on the raw request body, compared in constant time.
- [ ] The route accepts requests without a CSRF token or a signed-in user, and never redirects
      (Pixelforge does not follow redirects).
- [ ] A valid event is recorded once in `webhook_events` by its event id, a job is dispatched, and
      the answer is `200`; the request does no other work.
- [ ] The same event delivered twice (`[duplicate]`) is answered `200` both times and recorded and
      dispatched once.
- [ ] A `ping` is recorded and answered `200`, and applies nothing.

## Notes
