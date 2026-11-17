# 02. History cards

- Status: todo
- Blocked by: 01, generation-updates 03
- Spec: docs/features/history.md

## What to build

The bare history list becomes a grid of cards of the user's own generations, newest first, 20 per
page: status badge and hint, prompt, options, estimate, charge or "Not charged", the failure
message, and the creation time. Images come in the next ticket.

## Acceptance criteria

- [ ] A user sees only their own generations; another user's generation never appears on their
      history, on any page.
- [ ] Generations are listed newest first, 20 per page, with previous and next links.
- [ ] With no generations, the page shows "No generations yet" and a link to Generate.
- [ ] After submitting, the page shows "Generation submitted" and the new generation first,
      `pending`.
- [ ] Each card shows the status badge, the status's hint, the prompt cut to 3 lines and expandable,
      the size, the quality, the number of reference images and the relative creation time with the
      exact time on hover.
- [ ] Every card shows its estimate range; a succeeded card also shows its charge; a failed card
      shows "Not charged". Amounts use 4 decimals with the exact amount on hover.
- [ ] A `[fail]` generation's card shows the `provider_failed` message; a `[blocked]` one the
      `prompt_rejected` message; an `[overloaded]` one the `provider_unavailable` message.
- [ ] No page shows a provider cost, a token count or a provider error code.

## Notes
