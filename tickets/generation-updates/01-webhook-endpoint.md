# 01. Webhook endpoint

- Status: done
- Blocked by: generating 06
- Spec: docs/features/generation-updates.md

## What to build

Pixelforge can post webhooks to the app. The endpoint checks the signature on the raw body and the
timestamp, records each event once by its id, dispatches a job to apply it, and answers `200`; a
duplicate is answered `200` and not dispatched again, a `ping` is recorded and applies nothing (ADR
0010). The job only needs to exist here; applying events comes next.

## Acceptance criteria

- [x] A request with no signature, a wrong signature, a signature over a different body, or a
      timestamp more than 5 minutes off gets `400`, and no event, job or change is recorded.
- [x] The signature is checked on the raw request body, compared in constant time.
- [x] The route accepts requests without a CSRF token or a signed-in user, and never redirects
      (Pixelforge does not follow redirects).
- [x] A valid event is recorded once in `webhook_events` by its event id, a job is dispatched, and
      the answer is `200`; the request does no other work.
- [x] The same event delivered twice (`[duplicate]`) is answered `200` both times and recorded and
      dispatched once.
- [x] A `ping` is recorded and answered `200`, and applies nothing.

## Notes

- The route is registered in `bootstrap/app.php` under `withRouting(then:)`, outside the `web` group:
  no session, no CSRF token; it answers only `200` or `400`.
- The controller hands the raw body and the request's `HeaderBag` (a typed object, ADR 0041) to
  `RecordWebhookEvent`; `PixelforgeImageProvider::verifyWebhook()` checks the signature with
  `hash_equals` on `<timestamp>.<raw body>` and the ±5-minute window, and returns our
  `VerifiedWebhook`.
- With no webhook secret configured, every webhook is refused, since an empty HMAC key could be
  forged by anyone.
- The event row and its job commit in one transaction (database queue), so a failed dispatch leaves
  nothing and the provider's retry dispatches. Concurrent duplicates are stopped by the unique event
  id and `createOrFirst` (checked in review; no concurrency test).
- The apply job is empty until ticket 02.
