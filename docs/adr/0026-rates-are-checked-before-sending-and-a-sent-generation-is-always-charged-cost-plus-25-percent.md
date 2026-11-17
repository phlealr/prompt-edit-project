# 0026. Rates are checked before sending, and a sent generation is always charged cost plus 25%

- Status: accepted
- Date: 2026-11-17

## Context

The hold is the estimate's maximum at the pricing we had at submit (ADR 0006, ADR 0011), but
Pixelforge bills each generation at the rates in force when **it** created the generation
(`docs/spec/pixelforge-api.md#the-generation-object`). If the rates rise in between, the charge can
exceed the hold. Users pay Pixelforge's cost plus 25% (`docs/spec/assessment.md#what-to-build`), and
every generation must be recorded at exactly the right cost
(`docs/spec/assessment.md#what-we-look-at`). Pixelforge has no way to cancel a generation once
created, and a create that timed out may still have been created
(`docs/spec/pixelforge-api.md#idempotency`).

## Decision

At submit, a generation stores the rates its hold was computed with. Before each create attempt, the
submission job fetches the provider's pricing fresh, skipping the cache; if any rate differs from the
stored ones, it does not send, fails the generation with `price_changed` and releases the hold. The
check is skipped after an attempt that ended in a network error or timeout, because the provider may
already have created the generation. Once a generation is sent, its charge is always the provider
cost × 1.25, rounded up (ADR 0005), even when that exceeds the hold. In that case the user's balance
can go below zero, which blocks their next generation until they buy credits, and a warning is
logged.

## Rules it creates

- A generation stores the rates its hold was computed with.
- Before each create attempt, except after a network error or timeout, the job compares fresh
  provider rates with the stored ones; any difference fails the generation with `price_changed` and
  releases its hold, and nothing is sent.
- The fresh rates for this check never come from the cache.
- A charge is always ceil(provider cost × 5 / 4); it is never capped at the hold.
- A charge larger than its hold logs a warning with the generation, the hold, the charge and the
  difference.
- A generation whose charge exceeds its hold, and a user with a negative balance, can be found from
  the ledger alone; the operator report lists them as uncovered charges (ADR 0016).

## Consequences

Our profit on every sent generation is exactly the markup, and a rate change caught in time costs the
user nothing but a resubmit. Each create attempt costs one extra pricing request, which needs no API
key. A rate change in the milliseconds between the check and the creation, or during a timeout retry,
can still push a balance below zero; that money is uncollected until the user buys credits, and the
operator report shows it.

## Alternatives considered

- **Cap the charge at the hold**: the balance never goes negative, but we would earn less than the
  25% markup on that generation.
- **Charge above the hold without checking first**: simpler, but every rate change would put balances
  below zero instead of almost never.
- **Check after a timeout too**: the provider may already have created and billed the generation, and
  failing it locally would leave a provider cost we never charge.

## In plain words

Just before sending, we check that Pixelforge's prices are still the ones we held for; if not, we
don't send and the user resubmits at the new price. Once something is sent, the user always pays cost
plus 25%, no exceptions. In the tiny gap we can't check, the balance can dip below zero, and the
report shows us that money.
