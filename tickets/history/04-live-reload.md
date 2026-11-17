# 04. Live reload

- Status: todo
- Blocked by: 02
- Spec: docs/features/history.md

## What to build

While any generation on the current page is not final, the page reloads its data from our server
every 3 seconds and updates the cards in place; once all are final it stops (ADR 0028).

## Acceptance criteria

- [ ] While a generation on the current page is not final, the page reloads every 3 seconds, and the
      card's status changes without the user doing anything.
- [ ] When every generation on the current page is final, the page stops reloading.
- [ ] A reload only reads our database; it never calls the provider.
- [ ] A reload keeps the current page and scroll position.

## Notes
