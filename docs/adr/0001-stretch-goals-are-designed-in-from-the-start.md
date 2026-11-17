# 0001. Stretch goals are designed in from the start and built last

- Status: accepted
- Date: 2026-11-17

## Context

The brief has two stretch goals, balances and an operator report, to be built "only once everything
above works" (`docs/spec/assessment.md#stretch-goals-bonus-points`). Both depend on how money is
stored. A data model that only records "a charge per generation" would have to be reworked late to
support balances, in the most heavily graded part of the app.

## Decision

The data model and the money flow support balances and the operator report from the first ticket.
Their screens (balance display, "Buy credits", the operator report) are built after every required
feature works.

## Rules it creates

- Tickets for balance and report screens are blocked by every ticket of the required features.
- Money is recorded in the ledger from the first charge (ADR 0006), not in a structure that would
  have to be migrated for balances.

## Consequences

The first money tickets are slightly larger, because they write ledger rows instead of a single
column. In exchange, adding the stretch goals adds screens, not a change to how money is stored.

## Alternatives considered

- **Build the required part first, design the stretch goals later**: the money model would likely
  change after it is already tested and graded-critical.
- **Skip the stretch goals**: forgoes bonus points that the chosen design makes cheap.

## In plain words

I designed the money model for balances and reporting from day one, but built those screens last.
That way the stretch goals added pages, not a rewrite of the money code.
