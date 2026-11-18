# 0045. The markup is configurable, and each generation keeps the markup of its hold

- Status: accepted
- Date: 2026-11-18
- Amends: ADR 0005, ADR 0011

## Context

Users pay the provider cost plus a 25% markup, rounded up to a whole nanodollar, and the markup lived
as a constant inside `Money` (ADR 0005). The user wants it configurable from the environment. A
generation's hold is computed at submission and its charge at completion; if the setting changed in
between, the charge could use another markup than its hold and estimate showed.

## Decision

The markup is a whole percentage set by `MARKUP_PERCENT`, 25 by default (`config/credits.php`). The
provider estimates its own costs only; our actions add the markup. Each generation stores the markup
percent of its hold, and its estimate, hold and charge all use that stored value.

## Rules it creates

- The markup percentage comes from `credits.markup_percent` (env `MARKUP_PERCENT`, default 25).
- A charge is ceil(cost × (100 + markup) / 100), in integer arithmetic, inside `Money` only.
- The provider's estimate is in provider costs; markup is applied outside the provider.
- A generation stores its markup percent at submission, and its charge uses it.

## Consequences

The markup can change without a deploy of code, and a change affects only new generations. With the
default, every amount is the same as before. One more column on generations.

## Alternatives considered

- **Charge the markup current at completion**: no column, but a generation running across a change
  would be charged a markup its hold and estimate did not show.

## In plain words

The 25% is a setting now, 25 unless changed. Each generation remembers the markup it was quoted, so
it is always charged exactly what its estimate promised.
