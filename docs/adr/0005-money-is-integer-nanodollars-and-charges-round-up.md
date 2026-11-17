# 0005. Money is integer nanodollars, and the user's charge rounds up to a whole nanodollar

- Status: accepted
- Date: 2026-11-17

## Context

Pixelforge amounts are exact decimals with nine decimal places, and its docs warn that floats drift
(`docs/spec/pixelforge-api.md#worked-example-the-most-a-generation-can-cost`). Users pay Pixelforge's
cost plus a 25% markup, and the brief leaves handling fractions of a cent to us
(`docs/spec/assessment.md#what-to-build`). A provider cost is always a whole number of nanodollars,
but cost × 1.25 is not when the cost is not divisible by 4.

## Decision

Every amount of money is an integer number of nanodollars (1 USD = 1,000,000,000 nanodollars),
stored in `bigint` columns and handled through the `Money` class. A user's charge is the provider
cost × 1.25, computed with integer arithmetic and rounded **up** to the next whole nanodollar, once,
on the final amount.

## Rules it creates

- Every money column is a `bigint` holding nanodollars.
- No `float` or `double` holds money, in PHP, in SQL or in a cast.
- Decimal strings from a provider (rates, `cost_usd`) are converted to nanodollars by exact string
  parsing, never through a float.
- Markup and rounding happen only inside the `Money` class: charge = ceil(cost × 5 / 4) in integer
  arithmetic.
- Rounding is applied once, to the final charge, never to intermediate amounts.

## Consequences

Arithmetic and SQL sums are exact and simple. Amounts must be converted at the edges: parsing
provider strings and formatting for display (ADR 0007). Rounding up adds at most 0.75 nanodollars
to a charge, so the user never pays less than cost plus 25%.

## Alternatives considered

- **Decimal strings with `DECIMAL(20,9)` and bcmath**: exact, but every operation goes through string
  math functions.
- **A money library (e.g. brick/money)**: exact and expressive, but a new dependency to explain.
- **Round half up**: same precision, but can charge a fraction of a nanodollar below cost plus 25%.
- **Round to whole cents**: distorts the markup heavily on cheap generations (a $0.011 cost would be
  charged $0.02, +82%).

## In plain words

All money is whole nanodollars, which matches Pixelforge's nine decimal places exactly, so there is no
floating-point drift. The 25% markup is integer math rounded up once, so a user is never charged less
than cost plus 25%, and never more than a fraction of a nanodollar above it.
