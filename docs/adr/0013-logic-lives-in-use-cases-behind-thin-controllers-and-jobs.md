# 0013. Logic lives in use-case classes; controllers and jobs are thin entry points

- Status: accepted
- Date: 2026-11-17

## Context

A generation can change state from three entry points: the submission job, the webhook job and the
polling job (ADRs 0009, 0010). If each held its own copy of "update the status and charge", the rules
that keep money correct would exist in three places. The code must be easy to read and change
(`docs/spec/assessment.md#what-we-look-at`), and Laravel 13 lets controllers, jobs and models declare
their configuration with PHP attributes.

## Decision

All business logic lives in use-case classes, each doing one thing through one public method.
Controllers are single-action (`__invoke`) and only validate the request (a Form Request), call one
use case and return a response. Jobs only call one use case. A `Money` class holds all nanodollar
arithmetic and formatting. Configuration is declared with Laravel 13 PHP attributes wherever one
exists (`#[Tries]`, `#[Backoff]` and `#[Timeout]` on jobs, `#[Middleware]` and `#[Authorize]` on
controllers, `#[Fillable]`, `#[Hidden]` and `#[Table]` on models).

## Rules it creates

- Every controller has a single `__invoke` method and contains no business logic: no queries, no
  calculations, no provider calls.
- Every job's `handle` calls one use case and contains no business logic. Retry policy (attempts,
  backoff, timeout) is job configuration; the decision that a provider response means "retry later"
  comes from the use case.
- Every use case has one public method.
- A use case is called the same way from a controller, a job or a command.
- When Laravel offers an attribute for a configuration, the attribute is used instead of a property
  or method.

## Consequences

The rules for "never twice" and "never backwards" are written and tested once, and every entry point
is a few lines to read. There are more, smaller classes than in a default Laravel app. Some older
examples and tutorials will look different from this code because of the attributes.

## Alternatives considered

- **Logic in controllers and models (Laravel defaults)**: faster to start, but the polling and
  webhook jobs cannot call a controller, so the money logic would be duplicated.
- **Logic inside single-action controllers**: same problem; jobs are not HTTP requests.
- **Domain modules (`app/Domain/...`)**: more structure than this app needs and more to explain.
- **Properties and methods for configuration**: works, but is the older Laravel style.

## In plain words

Controllers and jobs are just entry points; every piece of logic is a use-case class they call. That
is why the webhook, the poller and the submission all share one piece of code that updates a
generation and charges it.
