# 02. History cards

- Status: done
- Blocked by: 01, generation-updates 03
- Spec: docs/features/history.md

## What to build

The bare history list becomes a grid of cards of the user's own generations, newest first, 20 per
page: status badge and hint, prompt, options, estimate, charge or "Not charged", the failure
message, and the creation time. Images come in the next ticket.

## Acceptance criteria

- [x] A user sees only their own generations; another user's generation never appears on their
      history, on any page.
- [x] Generations are listed newest first, 20 per page, with previous and next links.
- [x] With no generations, the page shows "No generations yet" and a link to Generate.
- [x] After submitting, the page shows "Generation submitted" and the new generation first,
      `pending`.
- [x] Each card shows the status badge, the status's hint, the prompt cut to 3 lines and expandable,
      the size, the quality, the number of reference images and the relative creation time with the
      exact time on hover.
- [x] Every card shows its estimate range; a succeeded card also shows its charge; a failed card
      shows "Not charged". Amounts use 4 decimals with the exact amount on hover.
- [x] A `[fail]` generation's card shows the `provider_failed` message; a `[blocked]` one the
      `prompt_rejected` message; an `[overloaded]` one the `provider_unavailable` message.
- [x] No page shows a provider cost, a token count or a provider error code.

## Notes

- Each card is a `GenerationCard` (ADR 0041) inside Laravel's paginator; it carries no provider
  cost, token count or provider error code. The charge is read from the ledger's charge row.
- Status hints come from `GenerationStatus::hint()`; a failed card shows its failure reason's
  message.
- Relative and exact times use the browser's `Intl`, in English and the user's own time zone; no
  new dependency.
- Manual checks on the local app, 2026-11-18: cards show badge, hint, a 3-line prompt that expands,
  options, "Estimate $a – $b", "Charged $x" or "Not charged", "48 seconds ago" with the exact local
  time on hover; 25 generations page as 20 + 5 with Next and Previous; a user with none sees "No
  generations yet" and a link to Generate.
