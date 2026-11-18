# 01. Buy credits on a Credits page

- Status: done
- Blocked by: generating 04
- Spec: docs/features/balances.md

## What to build

A Credits page in the sidebar shows the balance and the buy form: an amount in whole dollars, quick
buttons that fill it, and a Buy credits button that inserts one purchase (ADR 0029). The activity
list comes in a later ticket.

## Acceptance criteria

- [x] The quick buttons fill the amount field with 5, 10 or 25 and buy nothing on their own.
- [x] Buying 10 inserts exactly one `purchase` row of 10,000,000,000 nanodollars for that user and
      shows "Added $10.0000 to your balance".
- [x] Amounts 1 and 1,000 are accepted; 0, −5, 10.50, 1,001 and an empty field are rejected on the
      server with the field's message, and insert nothing.
- [x] The Buy credits button is disabled while its request is in flight.
- [x] Guests cannot buy credits.

## Notes

- `POST /credits` validates a whole number of dollars from 1 to 1,000 with one message, inserts one
  `purchase` through `BuyCredits` (`Money::fromWholeDollars()`), flashes "Added $X to your balance"
  under `purchase`, and redirects back, so the same form works on Generate (balances 03).
- `BuyCreditsForm.vue` fills the field from the $5/$10/$25 buttons and disables Buy credits while its
  request is in flight; the sidebar and header gained Credits.
- Manual checks on the local app, 2026-11-18: $25 then $10 filled the field with 10 and bought
  nothing; Buy credits was disabled in flight; buying showed "Added $10.0000 to your balance",
  emptied the field and the balance became $10.0000; 10.50 showed "Enter a whole number of dollars
  between 1 and 1,000.".
