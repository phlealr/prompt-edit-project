# 0037. Accounts cannot be deleted

- Status: accepted
- Date: 2026-11-18

## Context

The starter kit lets a user delete their account. Ledger rows reference their user and are never
updated or deleted (ADR 0006, ADR 0036), and the operator report reconciles our provider costs with
the provider's usage per day (ADR 0016). Deleting a user's rows would break both; keeping them makes
the user's deletion fail on the foreign key. The brief does not ask for account deletion
(`docs/spec/assessment.md#what-to-build`).

## Decision

Account deletion is removed: the "Delete account" section, its route, its controller method and its
Form Request are gone.

## Rules it creates

- No route deletes a user.
- A user's ledger rows and generations are never deleted.

## Consequences

Every amount ever moved stays explainable, and the operator report always matches the provider. A
user who wants to leave cannot remove their account themselves.

## Alternatives considered

- **Soft-delete users**: keeps the rows, but changes the starter kit's delete flow and every query that
  must hide deleted users.
- **Cascade delete**: simple, but deletes ledger rows (against ADR 0006) and breaks the report's
  reconciliation for the days that user generated.

## In plain words

Users can't delete their accounts, because every credit movement and generation must stay on record
for the money to add up and match Pixelforge. The brief doesn't ask for account deletion.
