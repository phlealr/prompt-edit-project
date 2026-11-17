# 0004. Queued jobs use the database driver, and the driver stays swappable

- Status: accepted
- Date: 2026-11-17

## Context

Submissions (ADR 0009) and webhook processing (ADR 0010) run in queued jobs. The load is small:
Pixelforge allows 60 requests a minute and 3 generations at once per account
(`docs/spec/pixelforge-api.md#rate-limits-and-quotas`). A generation and the job that submits it are
created together; if they are saved separately, a job can run before its generation is committed, or
survive a rolled-back generation.

## Decision

The queue uses Laravel's `database` driver on PostgreSQL in every environment. Jobs dispatched inside
a database transaction are saved in that same transaction. Changing the driver later (for example to
Redis) must stay a configuration change.

## Rules it creates

- `QUEUE_CONNECTION` is `database` in every environment.
- Jobs are dispatched only through Laravel's queue API; no code reads or writes the `jobs` table
  directly or uses a driver-specific feature (ADR 0014).

## Consequences

No extra service to run, locally or on Laravel Cloud, and a generation and its job are saved
atomically. Jobs are picked up by polling the table, a moment slower than Redis, and Horizon (a
Redis-only dashboard) is not available. When switching to Redis, jobs dispatched inside
transactions must be set to dispatch after commit.

## Alternatives considered

- **Redis**: instant pick-up, Horizon, and scales far higher, but it is one more service, and
  dispatching inside transactions needs extra care to avoid a race; none of its benefits matter at
  this load.

## In plain words

I used the database as the queue because it is one less service and it saves the generation and its
job in the same transaction. If volume ever demands Redis, it is a configuration change.
