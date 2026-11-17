# 0016. Operators are flagged users, and the operator report reconciles with the provider's usage

- Status: accepted
- Date: 2026-11-17

## Context

The admin report is "a page for us, the operators" showing credit spend, provider costs and profit
for each day and each user; provider costs should match `GET /v1/usage`, and the reviewers will
check them (`docs/spec/assessment.md#stretch-goals-bonus-points`). Pixelforge bills a generation on
the UTC day it completed, not the day it was created, and reports exact nine-decimal amounts per API
key (`docs/spec/pixelforge-api.md#usage`).

## Decision

An operator is a user with `is_admin` set, granted with `php artisan user:make-admin {email}`. The
report page is authorised by a Gate through `#[Authorize]`. The report groups everything by the UTC
day each generation completed, showing per day and per user: user spend (charges), provider cost and
profit. It also fetches the provider's usage for the same days (cached briefly) and shows our
provider cost, the provider's cost and the difference for each day, highlighting any non-zero
difference. Amounts are exact (ADR 0007).

## Rules it creates

- Only users with `is_admin` can open the report; everyone else gets 403.
- `is_admin` is set only by the artisan command, never through a web form.
- Report figures are grouped by the generation's completion time in UTC.
- Profit is user spend minus provider cost, per row.
- Each day shows our provider cost, the provider's reported cost and their difference.
- The provider's usage is fetched through the provider interface (ADR 0014).

## Consequences

The page proves, rather than claims, that our records match Pixelforge's. Loading it calls the
provider's API, so it depends on the provider being reachable and is cached briefly. The command is
the only way to make an operator, which is fine for a handful of operators.

## Alternatives considered

- **Admin emails in configuration**: no migration, but access depends on deployment configuration.
- **A roles and permissions package**: overkill for one role.
- **Group by creation day**: would not match Pixelforge, which bills by completion day.
- **Show only our own numbers**: simpler, but does not show that they match.

## In plain words

Operators are users with an admin flag set from the command line. The report groups costs by the UTC
day a generation completed, because that is how Pixelforge bills, and shows our cost next to
Pixelforge's so any difference is visible at a glance.
