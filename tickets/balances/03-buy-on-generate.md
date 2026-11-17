# 03. Buy on Generate

- Status: todo
- Blocked by: 01
- Spec: docs/features/balances.md

## What to build

In the "Not enough credits" state, the generate page shows the buy form right there. Buying keeps
everything the user typed, updates the balance, and enables Generate once the balance covers the
estimate's maximum.

## Acceptance criteria

- [ ] When the balance is below the estimate's maximum, Generate is disabled and the page shows the
      "Not enough credits" message with the buy form.
- [ ] Buying on Generate keeps the prompt and options, updates the balance, and enables Generate
      when the balance now covers the estimate's maximum.

## Notes
