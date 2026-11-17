# 03. Succeeded: cost and charge

- Status: todo
- Blocked by: 02
- Spec: docs/features/generation-updates.md

## What to build

A succeeded update records the usage, the rates on the generation, the provider cost computed from
them exactly, the charge of cost plus 25% rounded up, the image URL and its expiry, and the
provider's timestamps, and inserts the release and the charge in one transaction. A charge above the
hold is still charged in full and logged (ADR 0005, ADR 0006, ADR 0026).

## Acceptance criteria

- [ ] `generation.succeeded` sets `succeeded` and records usage, rates, provider cost, charge, image
      URL, its expiry, and the provider's `started_at` and `completed_at`.
- [ ] For the worked example's usage at the published rates (115 text, 1,530 image, 6,450 output and
      8,000 reasoning tokens), the provider cost is $0.373875000 and the charge is $0.467343750.
- [ ] The provider cost uses the rates on the generation even when the current pricing differs.
- [ ] A succeeded generation has exactly one `release` equal to its hold and one `charge` equal to
      ceil(provider cost × 5 / 4).
- [ ] Applying the same succeeded or failed update twice leaves one `release` and at most one
      `charge`.
- [ ] When the charge exceeds the hold, the full charge is recorded, a warning is logged with the
      generation, hold, charge and difference, and the balance may be negative.
- [ ] A user whose balance is negative cannot submit a generation.
- [ ] Generations whose charge exceeds their hold, and users with a negative balance, can be listed
      from the ledger alone.

## Notes
