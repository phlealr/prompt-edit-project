# 04. Live reload

- Status: done
- Blocked by: 02
- Spec: docs/features/history.md

## What to build

While any generation on the current page is not final, the page reloads its data from our server
every 3 seconds and updates the cards in place; once all are final it stops (ADR 0028).

## Acceptance criteria

- [x] While a generation on the current page is not final, the page reloads every 3 seconds, and the
      card's status changes without the user doing anything.
- [x] When every generation on the current page is final, the page stops reloading.
- [x] A reload only reads our database; it never calls the provider.
- [x] A reload keeps the current page and scroll position.

## Notes

- Inertia's `usePoll` reloads only the `generations` prop every 3 s (a constant in the page, ADR
  0028), started and stopped by a watch on "any card not final"; `mode: 'rest'` waits 3 s after each
  answer so slow answers never pile up. Inertia keeps the page and scroll position on reloads.
- A feature test sends a partial reload and checks the fake provider was not called; an arch test
  keeps the history page's controller and action off the provider.
- Manual checks on the local app, 2026-11-18: about one reload every 3 s while a card was
  processing; marking it succeeded updated the card with no action; reloads stopped once every card
  was final; `?page=1` and a 600 px scroll position were kept across reloads; the newly succeeded
  card's image switched to "Image expired" at its expiry (the watch fix of history 03).
