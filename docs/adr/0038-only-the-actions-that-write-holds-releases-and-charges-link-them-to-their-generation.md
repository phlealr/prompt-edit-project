# 0038. Holds, releases and charges always carry their generation, enforced by the actions

- Status: accepted
- Date: 2026-11-18

## Context

The database allows one row per (generation, type) in the ledger (ADR 0006). PostgreSQL treats NULLs
as distinct in a unique index, so a `hold`, `release` or `charge` row without a generation would pass
that rule. A database `CHECK` would need raw SQL, which ADR 0014 reserves for its own ADR.

## Decision

The database does not require a generation on ledger rows. The actions that insert `hold`, `release`
and `charge` rows always set the generation, and their tests prove it.

## Rules it creates

- Every `hold`, `release` and `charge` row has a generation; only `purchase` rows have none.
- Only the actions that submit a generation and apply a provider update insert `hold`, `release` and
  `charge` rows.

## Consequences

The migration stays plain Laravel. The guarantee against a second charge depends on those actions
always setting the generation, which a small number of tested places do.

## Alternatives considered

- **A `CHECK` constraint** (generation required unless `purchase`): the database enforces it, at the
  cost of raw SQL and its own ADR.

## In plain words

Only purchases have no generation. The code that writes holds, releases and charges always links
them to their generation, so the database's one-charge-per-generation rule always applies.
