# 03. Buy on Generate

- Status: done
- Blocked by: 01
- Spec: docs/features/balances.md

## What to build

In the "Not enough credits" state, the generate page shows the buy form right there. Buying keeps
everything the user typed, updates the balance, and enables Generate once the balance covers the
estimate's maximum.

## Acceptance criteria

- [x] When the balance is below the estimate's maximum, Generate is disabled and the page shows the
      "Not enough credits" message with the buy form.
- [x] Buying on Generate keeps the prompt and options, updates the balance, and enables Generate
      when the balance now covers the estimate's maximum.

## Notes

- The "Not enough credits" message and the buy form sit just below the generate form (a form cannot
  hold another); buying redirects back with the page's state kept, and a watch on the shared balance
  asks for a new estimate, which enables Generate once it covers the maximum.
- Generate shows the purchase message next to its balance (the buy form there hides its own copy),
  so it stays after the "Not enough credits" box closes.
- Found while checking this ticket in the browser and fixed in its own commit: cached pricing could
  not be read back from a real cache store (see the previous commit).
- Manual checks on the local app, 2026-11-18: at -$1.0000, Generate was disabled with "Not enough
  credits…" and the buy form; buying $5 showed "Added $5.0000 to your balance", the balance became
  $4.0000, Generate was enabled, and the typed prompt and quality were kept.
