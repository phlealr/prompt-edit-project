# 0010. Webhooks are verified and recorded in the request, applied in a job, with polling as a safety net

- Status: accepted
- Date: 2026-11-17

## Context

Anyone can post to the webhook URL, so signatures must be verified against the raw body and old
timestamps rejected (`docs/spec/pixelforge-api.md#verifying-signatures`). A delivery counts only if
answered with a 2xx within 10 seconds, and slow work belongs in a queued job
(`docs/spec/pixelforge-api.md#responding-and-retries`). Events arrive at least once and in any order,
can beat the create request's response, and may never arrive at all, in which case the app can poll
(`docs/spec/pixelforge-api.md#delivery-is-at-least-once-in-any-order`). An event delivered twice must
never be recorded twice (`docs/spec/assessment.md#what-to-build`).

## Decision

The webhook request verifies the signature and timestamp, inserts the event into a `webhook_events`
table whose unique event id turns a duplicate into a no-op, dispatches a job, and returns `200`. The
job applies the provider update to the generation, found through our id in the event's metadata. A
scheduled job runs every minute, fetches from the provider the current state of generations that have
stayed non-final too long, and applies it the same way. Every path (submission response, webhook,
poll) changes generations through one shared use case.

## Rules it creates

- A request with a missing or invalid signature, or a timestamp more than 5 minutes off (Pixelforge's
  own example tolerance), gets a 4xx and changes nothing.
- The signature is checked against the raw request body, before any JSON decoding.
- `webhook_events` has a unique constraint on the provider's event id; a duplicate is answered `200`
  and not processed again.
- The webhook request does no work beyond verifying, recording and dispatching.
- Only one use case changes a generation's status or writes its charge, and the webhook job, the
  polling job and the submission job all call it.
- The polling job runs every minute; the age after which a generation is polled is set in
  configuration.

## Consequences

Duplicates are stopped twice, by the event table and by forward-only statuses with one charge per
generation (ADRs 0006, 0008). Pixelforge always gets a fast answer. A generation whose webhooks are
all lost still finishes, a few minutes later. The app stores every received event, which also serves
as an audit trail.

## Alternatives considered

- **Apply the event inside the request**: simpler, but slow work risks the 10-second limit, and the
  apply logic would differ from the polling path.
- **Webhooks only, no polling**: a generation whose deliveries all fail would stay unfinished forever.
- **Polling only, no webhooks**: ignores a required feature and adds latency.

## In plain words

The webhook endpoint only checks the signature, stores the event (a duplicate id is ignored) and
queues a job, so it always answers fast. A job then applies the update through the same code the
poller uses, and the poller catches any generation whose webhooks never arrived.
