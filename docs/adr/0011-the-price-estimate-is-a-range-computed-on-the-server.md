# 0011. The price estimate is a range computed on the server

- Status: accepted (amended by ADR 0045: the provider estimates costs; our markup is added outside it)
- Date: 2026-11-17

## Context

Users must see what a generation will cost before submitting, and that price is an estimate because
part of the cost is only known at completion (`docs/spec/assessment.md#what-to-build`). The text
tokens can be 15% either side of `ceil(characters / 4)` and the reasoning tokens fall within a range
set by quality (`docs/spec/pixelforge-api.md#how-tokens-are-counted`). Rates change, so they must be
read from `GET /v1/pricing`, not hard-coded (`docs/spec/pixelforge-api.md#pricing-and-token-usage`).

## Decision

The estimate is shown as a range of user prices (minimum to maximum, markup included), with a note
that the exact amount is charged on completion. The provider implementation computes it on the
server from the current pricing, which is cached for a few minutes. The form asks the server for a
new estimate, debounced, whenever the prompt, size, quality or number of reference images changes.
The maximum of the range is the hold (ADR 0006).

## Rules it creates

- Pricing rules and rates exist only in the provider implementation; the frontend contains no
  pricing logic.
- Rates are read from the provider's pricing endpoint and cached; the cache lifetime is set in
  configuration.
- The estimate's maximum equals the amount held on submission for the same input.

## Consequences

One implementation of pricing serves the estimate, the hold and the tests, and switching provider
does not touch the frontend. Each change to the form costs a server round trip, softened by
debouncing.

## Alternatives considered

- **Compute the estimate in JavaScript**: instant, but duplicates the pricing logic in a second
  language and puts provider knowledge in the frontend (against ADR 0014).
- **Show a single number**: hides that the final price varies.

## In plain words

The form shows a price range, from the cheapest to the most expensive the generation could be, and
the top of that range is what gets held. It is computed on the server from Pixelforge's live rates,
so there is only one copy of the pricing rules.
