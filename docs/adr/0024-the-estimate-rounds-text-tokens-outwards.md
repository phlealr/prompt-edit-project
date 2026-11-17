# 0024. The estimate rounds text tokens outwards

- Status: accepted
- Date: 2026-11-17

## Context

The estimate is a range whose maximum is the hold (ADR 0006, ADR 0011). Text tokens are estimated as
`ceil(characters / 4)`, and the real count is "within 15% of that estimate in either direction"
(`docs/spec/pixelforge-api.md#how-tokens-are-counted`). 15% of the estimate is often not a whole
number (3 tokens give 2.55 to 3.45), and the docs do not say how Pixelforge rounds its own count. If
the maximum is rounded down and Pixelforge rounds up, a charge can exceed its hold.

## Decision

The estimate's minimum uses the text token estimate × 0.85, rounded **down**; its maximum uses the
estimate × 1.15, rounded **up**. Both are computed in integer arithmetic.

## Rules it creates

- Minimum text tokens = floor(estimate × 85 / 100); maximum text tokens = ceil(estimate × 115 / 100),
  in integer arithmetic.
- No real text token count within 15% of the estimate, however Pixelforge rounds it, gives a charge
  above the estimate's maximum.

## Consequences

The hold always covers the charge's text tokens, whichever way Pixelforge rounds. The range can be one
text token wider at each end than strictly needed: $0.00000625 per token with markup, invisible at
4 decimals.

## Alternatives considered

- **Round inwards (minimum up, maximum down)**: a slightly tighter range, but if Pixelforge rounds its
  count up, a charge exceeds its hold and the balance can go negative.

## In plain words

When 15% of the token estimate isn't a whole number, we round the top of the range up and the bottom
down. That way the amount we hold always covers what the user is finally charged, at a cost of a
millionth of a dollar.
