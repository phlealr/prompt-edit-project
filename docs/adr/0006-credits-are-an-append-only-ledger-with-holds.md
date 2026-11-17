# 0006. Credits are an append-only ledger, with a hold placed on submit and settled on completion

- Status: accepted
- Date: 2026-11-17

## Context

Every generation must be charged exactly once, never twice, and failed generations are free
(`docs/spec/assessment.md#what-we-look-at`). The balances stretch goal requires blocking a generation
the user cannot afford (`docs/spec/assessment.md#stretch-goals-bonus-points`), but the real price is
only known when the generation finishes. The most a generation can cost is known up front: at most
+15% text tokens and the top of the reasoning range
(`docs/spec/pixelforge-api.md#how-tokens-are-counted`), so the actual cost never exceeds that maximum.

## Decision

All credit movements are rows in an append-only `credit_transactions` ledger, each linked to its user
and, where it applies, its generation. A user's balance is the sum of their rows. Row types are
`purchase` (credits bought), `hold` (on submit, minus the maximum possible charge), `release` (the
hold given back) and `charge` (the actual charge). On success: `release` and `charge`. On failure:
`release` only, so a failed generation costs nothing.

## Rules it creates

- Ledger rows are only inserted; no code updates or deletes them.
- The database enforces at most one row per (generation, type): one `hold`, one `release` and one
  `charge` per generation.
- A balance is always computed as the sum of the user's ledger rows; no column stores it.
- A generation is submitted only if the user's balance covers the hold; the balance check and the
  hold insert happen in one transaction that locks the user's row.
- A failed generation never has a `charge` row.

## Consequences

The database itself refuses a second charge for a generation, even when two webhooks race. Every
amount is explainable from its rows, and the operator report sums rows. Each generation writes up to
three rows, and the balance is computed rather than read from a column.

## Alternatives considered

- **Charge columns on generations and a `balance` column on users**: fewer tables, but the
  double-charge guard lives only in code, and the balance has no history.
- **Check the balance without a hold**: two quick submissions could both pass and overspend.
- **Allow negative balances**: contradicts "block generating images when the user can't afford it".

## In plain words

Every money movement is a new row, like a bank statement, and the database allows only one charge per
generation, so a duplicated webhook cannot charge twice. When you submit, the maximum possible price
is held; when it finishes, the hold is released and the exact price charged, or just released if it
failed.
