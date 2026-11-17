# 0035. Provider rates cross the interface as our own `Rates` type

- Status: accepted
- Date: 2026-11-17

## Context

A generation stores the rates its hold was computed with, and the submission job compares them with
fresh rates before sending (ADR 0026). So rates must leave the provider with the estimate and come
back to it. ADR 0014 says only our own types cross the interface. Pixelforge sends rates as decimal
strings keyed by its own token names (`docs/spec/pixelforge-api.md#pricing-and-token-usage`), and
comparing strings would see `"5.00"` and `"5.0"` as different prices.

## Decision

Rates cross the provider interface as `App\ImageProviders\Rates`: four `Money` amounts, each the
price of 1M tokens of one kind (input text, input image, output, reasoning). The provider maps its
own response to it. A generation stores it, and two `Rates` are equal when all four amounts are.

## Rules it creates

- The estimate carries its rates as a `Rates`, never as the provider's raw array.
- Rates are compared by their `Money` amounts, never as strings.

## Consequences

Rate comparison is exact and independent of how the provider writes its decimals. It is one more
class, and it assumes a provider prices these four kinds of token; a provider that prices
differently would need this type changed.

## Alternatives considered

- **An opaque array only the provider reads**: fewer classes, but the provider's names and decimal
  strings would cross the boundary and be stored as they are.

## In plain words

Rates are our own small type: four exact prices per million tokens. Comparing two of them compares
the amounts, so a provider writing "5.0" instead of "5.00" is not a price change.
