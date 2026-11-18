# 02. Applying processing and failed

- Status: done
- Blocked by: 01
- Spec: docs/features/generation-updates.md

## What to build

The webhook job turns an event into a provider update through the provider interface, finds the
generation by our id in the metadata, and applies it through the one action. `processing` moves the
generation forward; `failed` sets `provider_failed`, keeps the provider's code and message, and
releases the hold. Late, repeated and early events change nothing they should not (ADR 0025).

## Acceptance criteria

- [x] An event for an unknown generation, or with no generation id in its metadata, is recorded,
      answered `200`, applies nothing and logs a warning.
- [x] `generation.processing` moves a `queued` or `pending` generation to `processing`.
- [x] `[fail]`: the generation ends `failed` with `provider_failed`, the provider's code and message
      are kept, it has one `release` equal to its hold and no `charge`, and the user's balance
      equals the balance before submitting.
- [x] Both `generation_failed` and `internal_error` map to `provider_failed`, and neither is
      resubmitted.
- [x] A `generation.processing` applied after `generation.succeeded` or `generation.failed` changes
      nothing.
- [x] A webhook applied to a `pending` generation moves it forward and stores the provider's id; the
      submission job's later response then changes nothing.

## Notes

- `ImageProvider::updateFromWebhook()` returns our `WebhookUpdate`: our generation id from the
  metadata (only a whole number counts) and the `ProviderUpdate`. `ApplyWebhookEvent` applies it
  through `ApplyProviderUpdate`, or logs a warning when it names no generation of ours.
- Guard added in review: `ApplyProviderUpdate` ignores, with a warning, an update whose provider id
  differs from the one stored, so a late event for another provider generation (for example after a
  reset, or from an environment sharing a key) cannot touch the wrong generation's money.
- The provider's error code and message are kept in the generation's raw `provider_response`.
- A signed but malformed payload makes the job fail into `failed_jobs`; the event stays recorded and
  polling settles the generation (the spec's "webhook job fails" flow). No test drives it.
- The fake's `updateFromWebhook()` delegates to the Pixelforge implementation, since webhook tests
  post real Pixelforge bodies.
