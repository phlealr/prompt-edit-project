# 0003. PostgreSQL is used locally, in production and in tests

- Status: accepted
- Date: 2026-11-17

## Context

The app must never record a charge twice and must reach the right state when webhooks arrive twice
or out of order (`docs/spec/assessment.md#what-we-look-at`,
`docs/spec/pixelforge-api.md#delivery-is-at-least-once-in-any-order`). Those guarantees rely on
transactions, row locks and unique constraints. SQLite, Laravel's default, does not support row locks
(`lockForUpdate` does nothing there), so tests on SQLite would not prove these guarantees.

## Decision

Every environment uses PostgreSQL: local development (in Docker), production (Laravel Cloud) and the
test suite, which runs against its own PostgreSQL database.

## Rules it creates

- The test suite connects to PostgreSQL, never SQLite.
- Tests use a separate database from local development.

## Consequences

Tests that check concurrency guarantees exercise the same engine as production. Tests run a little
slower than on in-memory SQLite, and Docker must be running locally.

## Alternatives considered

- **SQLite for local and tests, PostgreSQL in production**: faster tests, but locking and some
  constraint behaviour differ, so a passing test would prove less.
- **MySQL**: also supported by Laravel Cloud; no advantage here over PostgreSQL.

## In plain words

The money guarantees depend on database locks and unique constraints, so I test on the same database
engine I run in production. SQLite would let the tests pass without proving anything about locks.
