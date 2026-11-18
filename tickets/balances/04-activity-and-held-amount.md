# 04. Activity and held amount

- Status: done
- Blocked by: 01, generation-updates 03
- Spec: docs/features/balances.md

## What to build

The Credits page lists the user's purchases and charges, and shows how much of the balance is held
for generations still running, so the user can account for every cent.

## Acceptance criteria

- [x] Credits shows the balance, the buy form and the activity.
- [x] The activity lists purchases and charges only, newest first, 20 per page, each with its signed
      amount, date and time; a charge shows its generation's prompt and links to History.
- [x] While a generation is running, Credits shows its hold as the held amount; once it is final,
      the held amount no longer includes it.
- [x] The activity's purchases and charges, plus the held amount, add up to the balance shown.
- [x] A user never sees another user's balance or activity.
- [x] With no activity, the page shows "No activity yet. Buy credits to start generating."

## Notes

- `SummarizeCredits` returns a `CreditsSummary`: the held amount (holds with no release yet, as a
  positive amount, or none) and the activity (purchases and charges, newest first, 20 per page, each
  an `ActivityEntry`).
- For the user to confirm: criterion 4 and `docs/features/balances.md` say the activity "plus the held
  amount" adds up to the balance; since the held amount is shown positive, the arithmetic is purchases
  + charges − held = balance, which is what the code and its test check. The spec was left unchanged.
- Manual check on the local app, 2026-11-18: Credits showed "$0.4673 of it is held for generations
  still running…", purchases as "Bought credits +$5.0000" with local date and time, charges as
  "Generation: <prompt>" linking to History with a negative amount.
