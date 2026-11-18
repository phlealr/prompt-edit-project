# 03. Succeeded: cost and charge

- Status: done
- Blocked by: 02
- Spec: docs/features/generation-updates.md

## What to build

A succeeded update records the usage, the rates on the generation, the provider cost computed from
them exactly, the charge of cost plus 25% rounded up, the image URL and its expiry, and the
provider's timestamps, and inserts the release and the charge in one transaction. A charge above the
hold is still charged in full and logged (ADR 0005, ADR 0006, ADR 0026).

## Acceptance criteria

- [x] `generation.succeeded` sets `succeeded` and records usage, rates, provider cost, charge, image
      URL, its expiry, and the provider's `started_at` and `completed_at`.
- [x] For the worked example's usage at the published rates (115 text, 1,530 image, 6,450 output and
      8,000 reasoning tokens), the provider cost is $0.373875000 and the charge is $0.467343750.
- [x] The provider cost uses the rates on the generation even when the current pricing differs.
- [x] A succeeded generation has exactly one `release` equal to its hold and one `charge` equal to
      ceil(provider cost × 5 / 4).
- [x] Applying the same succeeded or failed update twice leaves one `release` and at most one
      `charge`.
- [x] When the charge exceeds the hold, the full charge is recorded, a warning is logged with the
      generation, hold, charge and difference, and the balance may be negative.
- [x] A user whose balance is negative cannot submit a generation.
- [x] Generations whose charge exceeds their hold, and users with a negative balance, can be listed
      from the ledger alone.

## Notes

- Pixelforge computes the provider cost from the usage and the `rates` on its own generation object,
  and `ProviderUpdate` carries it as `Money` (ADR 0014); usage and the billed rates are kept in the
  generation's raw `provider_response` for audit, not in columns of their own. The generation's
  `rate_*` columns stay the rates the hold used.
- `ProviderUpdate` refuses a provider cost without the succeeded status and the reverse.
- `ApplyProviderUpdate` releases the hold and inserts the charge (cost × 5 / 4, rounded up, never
  capped) in the generation's lock; a charge above the hold logs a warning with the generation,
  hold, charge and difference in exact amounts.
- The provider's times are stored in UTC, since `completed_at` decides the billing day (ADR 0016).
- A create response the provider implementation cannot read becomes `ProviderUnreachable`, so the
  generation is marked as possibly existing and never failed by us (ADR 0040, ADR 0042).
- "Listed from the ledger alone" is proven by a test running the two ledger queries; the operator
  report (operator-report 04) builds the real listing.
