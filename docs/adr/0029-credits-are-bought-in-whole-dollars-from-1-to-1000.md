# 0029. Credits are bought in whole dollars, from $1 to $1,000

- Status: accepted
- Date: 2026-11-17

## Context

The balances stretch goal asks for a "Buy credits" button that adds credits to the user's account, as
a fake purchase with no real payments (`docs/spec/assessment.md#stretch-goals-bonus-points`). Credits
are US dollars, stored as nanodollars (ADR 0005), and every purchase is a ledger row (ADR 0006). New
users start with none (ADR 0017), so buying is the first thing a reviewer does.

## Decision

A purchase is a whole number of dollars, from $1 to $1,000. The buy form has one amount field and one
**Buy credits** button; quick buttons for $5, $10 and $25 fill the field, and the user can change it
before buying. Each confirmed purchase inserts one `purchase` row of that amount in nanodollars.

## Rules it creates

- A purchase amount is an integer number of dollars between 1 and 1,000, validated on the server.
- A purchase inserts exactly one `purchase` ledger row, of the amount × 1,000,000,000 nanodollars.
- Quick buttons only fill the amount field; only the Buy credits button buys.
- The Buy credits button is disabled while its request is in flight.

## Consequences

Buying is one or two clicks, and amounts stay round and easy to check against the ledger. A typo
cannot make a balance meaningless. There is no cap on the total balance, since the money is fake.

## Alternatives considered

- **Quick buttons that buy at once**: one click fewer, but a misclick buys.
- **Any amount, cents included**: nothing gained for fake money, and more to validate and display.
- **No upper limit**: a typo such as `1000000` would make the balance meaningless.

## In plain words

Users buy credits in whole dollars, between one and a thousand. The $5, $10 and $25 buttons just fill
in the amount, and nothing is bought until they press Buy credits.
