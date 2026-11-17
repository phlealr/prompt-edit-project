# 0036. Ledger rows are guarded against updates and deletes in the model

- Status: accepted
- Date: 2026-11-17

## Context

Ledger rows are only inserted; no code updates or deletes them (ADR 0006). The rule needs something
that stops a mistake, not only a convention. PostgreSQL-specific SQL, such as a trigger, needs its
own ADR (ADR 0014).

## Decision

The ledger model throws when code tries to update or delete a row, from its Eloquent `updating` and
`deleting` events.

## Rules it creates

- Updating or deleting a ledger row through Eloquent throws an exception, and the row is unchanged.

## Consequences

Any app code that tries to change the ledger fails loudly, on any database, and a test proves it.
Raw SQL or a query-builder `update()` could still bypass it; no app code does that.

## Alternatives considered

- **A PostgreSQL trigger**: also stops raw SQL, but is database-specific and needs its own ADR.
- **Both**: strongest, and the most to explain.

## In plain words

The ledger model refuses to be updated or deleted, so an append-only ledger can't be edited by
mistake. We chose this plain Laravel guard over a database trigger.
