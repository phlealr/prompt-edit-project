# 02. Applying processing and failed

- Status: todo
- Blocked by: 01
- Spec: docs/features/generation-updates.md

## What to build

The webhook job turns an event into a provider update through the provider interface, finds the
generation by our id in the metadata, and applies it through the one action. `processing` moves the
generation forward; `failed` sets `provider_failed`, keeps the provider's code and message, and
releases the hold. Late, repeated and early events change nothing they should not (ADR 0025).

## Acceptance criteria

- [ ] An event for an unknown generation, or with no generation id in its metadata, is recorded,
      answered `200`, applies nothing and logs a warning.
- [ ] `generation.processing` moves a `queued` or `pending` generation to `processing`.
- [ ] `[fail]`: the generation ends `failed` with `provider_failed`, the provider's code and message
      are kept, it has one `release` equal to its hold and no `charge`, and the user's balance
      equals the balance before submitting.
- [ ] Both `generation_failed` and `internal_error` map to `provider_failed`, and neither is
      resubmitted.
- [ ] A `generation.processing` applied after `generation.succeeded` or `generation.failed` changes
      nothing.
- [ ] A webhook applied to a `pending` generation moves it forward and stores the provider's id; the
      submission job's later response then changes nothing.

## Notes
