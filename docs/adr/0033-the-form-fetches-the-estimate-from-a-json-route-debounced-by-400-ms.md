# 0033. The form fetches the estimate from a JSON route, debounced by 400 ms

- Status: accepted
- Date: 2026-11-17

## Context

The estimate is computed on the server and refreshed, debounced, whenever the prompt, size, quality
or number of reference images changes (ADR 0011). The page needs a way to ask for it without leaving
the page or losing what the user typed, and a debounce delay.

## Decision

A `POST` route returns the estimate as JSON. The generate page calls it through Inertia's `useHttp`
with the route's Wayfinder function, debounced by 400 ms with `useDebounceFn` from `@vueuse/core`
(already installed). When pricing is unavailable, the route answers `503`.

## Rules it creates

- The estimate is fetched from its own JSON route, never through an Inertia page visit.
- The debounce delay is 400 ms.
- The route answers `503` when pricing is unavailable.
- The route estimates the form as it is being typed: an empty prompt and blank reference image fields
  are accepted, and only the number of reference images is used. Prompt length, sizes, qualities and
  at most 4 reference images are still validated.

## Consequences

The page's URL and props stay unchanged while the user types, and the estimate route can be tested on
its own. It is one more route and controller.

## Alternatives considered

- **Inertia partial reload** (`router.reload({ only: ['estimate'] })`): no extra route, but the form
  values go into the page's query string and each request runs the full page cycle.
- **250 ms or 800 ms**: more requests, or an estimate that lags behind typing.

## In plain words

The form asks a small JSON endpoint for the estimate 400 ms after the user stops typing. The page
itself never reloads, so nothing the user typed is lost.
