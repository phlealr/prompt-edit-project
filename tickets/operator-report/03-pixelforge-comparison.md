# 03. Pixelforge comparison

- Status: todo
- Blocked by: 02
- Spec: docs/features/operator-report.md

## What to build

Each day shows Pixelforge's own cost beside ours and the difference, highlighted when not zero. The
provider interface reports usage for any range; the Pixelforge implementation asks in pieces of at
most 92 days, cached briefly. When Pixelforge cannot be reached, our figures are shown alone (ADR
0016, ADR 0030).

## Acceptance criteria

- [ ] Each day shows Pixelforge's `cost_usd` for that day and the difference from our provider cost;
      a non-zero difference is highlighted.
- [ ] For a range over 92 days, the provider is asked in consecutive pieces of at most 92 days, and
      no request covers more than 92 days.
- [ ] Usage is cached for the configured time (default 1 minute), and the page shows when it was
      fetched.
- [ ] When any usage request fails, the provider's column shows "Unavailable" with the note, nothing
      is highlighted, and all our figures are shown.
- [ ] Nothing outside `app/ImageProviders/Pixelforge/` knows the 92-day limit.

## Notes
