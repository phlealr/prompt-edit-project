# 04. Activity and held amount

- Status: todo
- Blocked by: 01, generation-updates 03
- Spec: docs/features/balances.md

## What to build

The Credits page lists the user's purchases and charges, and shows how much of the balance is held
for generations still running, so the user can account for every cent.

## Acceptance criteria

- [ ] Credits shows the balance, the buy form and the activity.
- [ ] The activity lists purchases and charges only, newest first, 20 per page, each with its signed
      amount, date and time; a charge shows its generation's prompt and links to History.
- [ ] While a generation is running, Credits shows its hold as the held amount; once it is final,
      the held amount no longer includes it.
- [ ] The activity's purchases and charges, plus the held amount, add up to the balance shown.
- [ ] A user never sees another user's balance or activity.
- [ ] With no activity, the page shows "No activity yet. Buy credits to start generating."

## Notes
