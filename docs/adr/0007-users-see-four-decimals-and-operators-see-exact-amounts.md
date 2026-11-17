# 0007. Users see amounts with 4 decimals; operators see exact amounts

- Status: accepted
- Date: 2026-11-17

## Context

Typical prices range from fractions of a cent to tens of cents, and users must clearly see what
things cost (`docs/spec/assessment.md#what-we-look-at`). Pixelforge reports usage cost with nine
decimal places (`docs/spec/pixelforge-api.md#usage`), and reviewers compare the operator report with
it.

## Decision

User-facing pages show dollar amounts with 4 decimal places (`$0.0577`), with the exact amount on
hover. The operator report shows every amount with all 9 decimal places. Stored values are always
exact (ADR 0005); this decision is display only.

## Rules it creates

- Money is formatted only through the `Money` class, which offers the 4-decimal and the exact
  9-decimal formats.
- Every user-facing amount shows the exact amount on hover.
- Every amount on the operator report uses the exact 9-decimal format.

## Consequences

Cheap generations stay distinguishable from each other, and the report can be compared with
Pixelforge character for character. A 4-decimal value is a rounded view, which is why the exact
amount is one hover away.

## Alternatives considered

- **Cents (`$0.06`)**: familiar, but many cheap generations would all show `$0.01` and hide the real
  cost.
- **Always 9 decimals**: exact but noisy for users.

## In plain words

Users see four decimals so small prices stay readable and distinct, with the exact value on hover.
The operator report shows all nine decimals so it can be checked against Pixelforge exactly.
