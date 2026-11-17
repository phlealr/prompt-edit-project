# 0025. A generation's status only moves forward, and a failure records one of four reasons

- Status: accepted
- Date: 2026-11-17
- Supersedes: ADR 0008

## Context

Pixelforge has four statuses, `queued → processing → succeeded | failed`, and the last two are final
(`docs/spec/pixelforge-api.md#lifecycle`). Updates can arrive out of order, and an app must never move
a generation back from `succeeded` or `failed`
(`docs/spec/pixelforge-api.md#delivery-is-at-least-once-in-any-order`). Because submission is queued
(ADR 0009), a generation exists on our side before the provider accepts it. Users must see clearly
what went wrong (`docs/spec/assessment.md#what-we-look-at`). ADR 0026 adds a case ADR 0008 did not
have: a generation we decline to send because the provider's rates changed after its hold.

## Decision

A generation has five statuses, in this order: `pending` (saved by us, not yet accepted by the
provider), `queued`, `processing`, `succeeded`, `failed`. The status only moves forward in that order,
and never leaves `succeeded` or `failed`. A failed generation records a `failure_reason`:
`prompt_rejected` (the provider refused it at submission), `provider_failed` (accepted, then failed),
`provider_unavailable` (we gave up after the capped retries, or hit an error retrying cannot fix), or
`price_changed` (we did not send it because the provider's rates changed after its hold).

## Rules it creates

- An update that would move a status backwards, or out of `succeeded` or `failed`, is ignored without
  error.
- `failure_reason` is set if and only if the status is `failed`.
- Each failure reason has its own user-facing message.
- Statuses and failure reasons are defined once (as enums) and mapped from the provider's values at
  the provider boundary (ADR 0014).

## Consequences

Out-of-order and duplicate updates are harmless by construction, and the user sees a precise state
and reason. The app has one status (`pending`) and four failure reasons that Pixelforge does not
have, which the provider implementation and the submission job map to.

## Alternatives considered

- **Mirror Pixelforge's four statuses only**: cannot represent a generation waiting out a rate limit
  before the provider accepts it.
- **A single "failed" with the provider's raw error**: ties the UI to Pixelforge's error codes and
  cannot express "we gave up retrying" or "the price changed".
- **Report a price change as `provider_unavailable`**: the user would retry blindly instead of
  checking the new estimate.

## In plain words

A generation's status can only move forward, so a late or duplicated webhook can never undo a result.
We added a `pending` status for the time before Pixelforge accepts it, and every failure says why in
our own words, including "the price changed, check the new estimate".
