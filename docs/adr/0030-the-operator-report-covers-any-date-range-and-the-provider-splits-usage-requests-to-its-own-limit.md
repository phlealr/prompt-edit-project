# 0030. The operator report covers any date range, and the provider splits usage requests to its own limit

- Status: accepted
- Date: 2026-11-17

## Context

ADR 0016 shows, for each day, our provider cost beside the provider's own usage, so reviewers can check
that they match (`docs/spec/assessment.md#stretch-goals-bonus-points`). Pixelforge's `GET /v1/usage`
covers at most 92 days per request and rejects longer ranges with `422 date_range_too_long`
(`docs/spec/pixelforge-api.md#usage`). The user decided that our own system must not be limited by
that: the report is ours, and a provider's limit is the provider's detail (ADR 0014). ADR 0016 also
leaves "cached briefly" and an unreachable provider open.

## Decision

The operator picks any date range, defaulting to the last 30 UTC days ending today, with no maximum.
Our figures always come from our own database. The provider interface takes any date range for usage;
the Pixelforge implementation splits it into consecutive pieces of at most 92 days, requests each, and
joins the days. Each usage response is cached for 1 minute, and the page shows when the provider's
figures were fetched. If any request fails, the provider's column and the difference show
"Unavailable" for the whole range, our figures are still shown, and nothing is highlighted.

## Rules it creates

- The report accepts any range whose start is not after its end; it has no maximum length.
- The 92-day limit exists only in `app/ImageProviders/Pixelforge/`; the interface takes any range.
- The provider's usage is cached for a time set in configuration (default 1 minute), and the page
  shows the time it was fetched.
- When the provider's usage cannot be fetched, the report still shows every figure of ours and marks
  the provider's column "Unavailable" without highlighting any difference.

## Consequences

Operators are never limited by a provider's rules, and a provider with another limit, or none, changes
only its own implementation. A long range costs one provider request per 92 days, softened by the
cache. A just-completed generation can show a short-lived difference until its webhook is applied or
the cache expires; the "as of" time explains it.

## Alternatives considered

- **Limit the report to 92 days**: one request per view, but our report would be bound by
  Pixelforge's rule.
- **Drop the comparison**: the plainest report, but reviewers would lose the per-day proof that our
  costs match.
- **Show the provider's figures only for ranges of 92 days or less**: no splitting, but the comparison
  would vanish for long ranges.

## In plain words

The report covers any dates the operator picks. Pixelforge only answers 92 days at a time, so the
Pixelforge code asks in 92-day pieces and joins them; nothing else knows about that limit. If
Pixelforge can't be reached, our own numbers are still shown.
