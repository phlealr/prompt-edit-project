# 0039. Concurrency is tested with forked processes against committed data

- Status: accepted
- Date: 2026-11-18

## Context

Two submissions that together exceed the balance must not both create a hold; the balance check and
the hold run in one transaction that locks the user's row (ADR 0006). Feature tests wrap each test in
a transaction (`RefreshDatabase`), so a second connection cannot see their data, and one PHP process
cannot run two transactions at the same time.

## Decision

Tests that need several processes live in `tests/Concurrency/`. They truncate the tables before each
test (`DatabaseTruncation`) instead of using a transaction, and fork child processes with `pcntl`,
each with its own database connection. A test may slow a query down with a listener, so the race it
guards against happens every time.

## Rules it creates

- Tests that run several processes against the database live in `tests/Concurrency/<Area>/` and use
  `DatabaseTruncation`.
- The test suite needs the `pcntl` and `posix` PHP extensions.

## Consequences

The lock is proven by the outcome (one hold, not two), and the test fails when the lock is removed.
The suite needs two extensions, and these tests are slower and must not share data with other tests.

## Alternatives considered

- **A lock check with `SELECT … FOR UPDATE NOWAIT` from a second connection**: no new folder, but it
  tests the mechanism, not the outcome.
- **No test**: the criterion would rest on the code alone.

## In plain words

To prove two simultaneous submissions can't both spend the same balance, a test really runs two
processes at once. Removing the lock makes it fail, so it proves what it claims.
