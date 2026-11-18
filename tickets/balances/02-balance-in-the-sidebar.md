# 02. Balance in the sidebar

- Status: done
- Blocked by: 01, history 04
- Spec: docs/features/balances.md

## What to build

Every signed-in page shows the balance in the sidebar, linking to Credits, current on every page
load and on History's reloads. A balance below zero shows in the error colour, and Credits explains
why.

## Acceptance criteria

- [x] Every signed-in page shows the balance in the sidebar, with 4 decimals and the exact amount on
      hover, linking to Credits.
- [x] The sidebar balance updates on a History reload when a generation is charged or released.
- [x] A new user's balance is $0.0000 and their ledger is empty.
- [x] The balance equals the sum of the user's ledger rows, and no column stores it.
- [x] A negative balance shows in the error colour, and Credits shows the "below zero" message.

## Notes

- The balance is one shared Inertia prop, `balance` (a `Balance`: amount and `belowZero`), computed
  by a closure once per signed-in request; Generate and Credits read it instead of their own props.
  `belowZero` comes from the server, so the page never compares amounts.
- History's reloads ask for `generations` and `balance`, so a charge shows in the sidebar with no
  action.
- The sidebar item has an icon and a tooltip, so it still reads when the sidebar is collapsed.
- Manual checks on the local app, 2026-11-18: the sidebar showed "Balance $10.0000" linking to
  Credits with the exact amount on hover; a charge inserted while History was open turned it into
  "-$2.0000" in the error colour without any action; Credits showed the below-zero message.
