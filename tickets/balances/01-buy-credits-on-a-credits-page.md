# 01. Buy credits on a Credits page

- Status: todo
- Blocked by: generating 04
- Spec: docs/features/balances.md

## What to build

A Credits page in the sidebar shows the balance and the buy form: an amount in whole dollars, quick
buttons that fill it, and a Buy credits button that inserts one purchase (ADR 0029). The activity
list comes in a later ticket.

## Acceptance criteria

- [ ] The quick buttons fill the amount field with 5, 10 or 25 and buy nothing on their own.
- [ ] Buying 10 inserts exactly one `purchase` row of 10,000,000,000 nanodollars for that user and
      shows "Added $10.0000 to your balance".
- [ ] Amounts 1 and 1,000 are accepted; 0, −5, 10.50, 1,001 and an empty field are rejected on the
      server with the field's message, and insert nothing.
- [ ] The Buy credits button is disabled while its request is in flight.
- [ ] Guests cannot buy credits.

## Notes
