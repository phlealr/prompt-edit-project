# 03. Pixelforge comparison

- Status: done
- Blocked by: 02
- Spec: docs/features/operator-report.md

## What to build

Each day shows Pixelforge's own cost beside ours and the difference, highlighted when not zero. The
provider interface reports usage for any range; the Pixelforge implementation asks in pieces of at
most 92 days, cached briefly. When Pixelforge cannot be reached, our figures are shown alone (ADR
0016, ADR 0030).

## Acceptance criteria

- [x] Each day shows Pixelforge's `cost_usd` for that day and the difference from our provider cost;
      a non-zero difference is highlighted.
- [x] For a range over 92 days, the provider is asked in consecutive pieces of at most 92 days, and
      no request covers more than 92 days.
- [x] Usage is cached for the configured time (default 1 minute), and the page shows when it was
      fetched.
- [x] When any usage request fails, the provider's column shows "Unavailable" with the note, nothing
      is highlighted, and all our figures are shown.
- [x] Nothing outside `app/ImageProviders/Pixelforge/` knows the 92-day limit.

## Notes

- `ImageProvider::usage()` returns our `ProviderUsage` (a `UsageDay` per day, and when it was
  fetched) or throws `UsageUnavailable`. The Pixelforge implementation asks in pieces of at most 92
  days, caches each piece for the configured time (default 60 s) as one string, "<fetched at>\n<body>",
  and accepts a body only if it lists every day of its piece with an exact `cost_usd`.
- Each day's difference is our provider cost minus Pixelforge's; `highlighted` comes from the server,
  so the page never compares amounts. Without usage, both provider columns read "Unavailable", with
  the note, and nothing is highlighted.
- A plain test checks that no file in `app/` outside `ImageProviders/Pixelforge/` mentions 92.
- Manual check on the local app (no API key), 2026-11-18: the report showed "Couldn't reach
  Pixelforge's usage report. Our figures are shown alone." and "Unavailable" in both provider
  columns, with our figures intact. The available path is proven by the feature tests; the real
  comparison is operator-report 05, on the deployed app.
