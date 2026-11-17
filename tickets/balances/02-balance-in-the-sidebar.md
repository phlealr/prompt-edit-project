# 02. Balance in the sidebar

- Status: todo
- Blocked by: 01, history 04
- Spec: docs/features/balances.md

## What to build

Every signed-in page shows the balance in the sidebar, linking to Credits, current on every page
load and on History's reloads. A balance below zero shows in the error colour, and Credits explains
why.

## Acceptance criteria

- [ ] Every signed-in page shows the balance in the sidebar, with 4 decimals and the exact amount on
      hover, linking to Credits.
- [ ] The sidebar balance updates on a History reload when a generation is charged or released.
- [ ] A new user's balance is $0.0000 and their ledger is empty.
- [ ] The balance equals the sum of the user's ledger rows, and no column stores it.
- [ ] A negative balance shows in the error colour, and Credits shows the "below zero" message.

## Notes
