# 0031. User amounts round half up to four decimals

- Status: accepted
- Date: 2026-11-17

## Context

Users see amounts with 4 decimals and the exact amount on hover (ADR 0007), but stored amounts have 9
(ADR 0005). ADR 0007 does not say how the 5 dropped digits are handled: a charge of $0.00005 could
show as `$0.0001` or `$0.0000`.

## Decision

The 4-decimal user format rounds half up, away from zero: `$0.00005` shows as `$0.0001` and
`-$0.00005` as `-$0.0001`. The rounding is done in integer arithmetic inside `Money`.

## Rules it creates

- The user format of an amount is the amount rounded half up, away from zero, to 4 decimals.
- A negative amount shows a minus sign before the dollar sign (`-$0.4673`).

## Consequences

Users see the nearest 4-decimal amount, as they would on any price. A displayed amount can be up to
$0.00005 above or below the exact one, which is why the exact amount is on hover.

## Alternatives considered

- **Truncate**: never shows more than the exact amount, but a very cheap charge can show as
  `$0.0000` and look free.

## In plain words

Four-decimal amounts are rounded to the nearest value, the way people expect prices to be rounded.
The exact amount is always one hover away.
