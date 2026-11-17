# Operator report

- Status: approved
- Sources:
  - Brief: [Stretch goals](../spec/assessment.md#stretch-goals-bonus-points) (Admin report),
    [What we look at](../spec/assessment.md#what-we-look-at)
  - Pixelforge: [Usage](../spec/pixelforge-api.md#usage), [Errors](../spec/pixelforge-api.md#errors)
  - ADRs: 0001, 0005, 0006, 0007, 0014, 0016, 0020, 0026, 0030
  - Specs: [generation-updates](generation-updates.md), [balances](balances.md)

## Goal

Operators see, for any range of days, what users were charged, what the provider charged us, and the
profit, per day and per user, with the provider's own usage beside our provider cost for each day so
anyone can see they match. Uncovered charges are listed. This meets the brief's **Admin report**
stretch goal.

## Behaviour

### Who can open it

- An operator is a user with `is_admin` set (ADR 0016). `php artisan user:make-admin {email}` sets it;
  an unknown email prints an error and changes nothing. No web form sets it.
- Operators see **Operator report** in the sidebar; other users do not.
- A signed-in user who is not an operator gets `403`; a guest is redirected to sign in.

### The date range

- A start and an end date, in UTC, inclusive. Default: the last 30 days, ending today.
- Any range whose start is not after its end is accepted, with no maximum (ADR 0030). A start after
  the end shows "The start date must be on or before the end date." and keeps the previous report.
- Every day in the range has a row, including days with no generations.

### What is counted

- A generation counts on the UTC day it **completed**, from the provider's `completed_at` (ADR 0016).
  Only succeeded generations have a cost; failed ones count nowhere.
- **User spend**: the sum of the `charge` rows of those generations.
- **Provider cost**: the sum of their recorded provider costs.
- **Profit**: user spend minus provider cost, per row.
- **Pixelforge's cost**: `cost_usd` from the provider's usage for that day.
- **Difference**: our provider cost minus Pixelforge's cost.
- Every amount is shown with all 9 decimals (ADR 0007).

### The page, top to bottom

1. **Range**: the two dates and a Show button, and "Pixelforge figures as of 14:03 UTC".
2. **Totals** for the range: generations, user spend, provider cost, profit, uncovered charges.
3. **Per day**: one row per day, newest first: date, generations, user spend, our provider cost,
   Pixelforge's cost, difference, profit. A non-zero difference is highlighted. Clicking a day opens
   its per-user rows: user email, generations, user spend, provider cost, profit.
4. **Per user**: one row per user with generations in the range: user email, generations, user spend,
   provider cost, profit; highest spend first.
5. **Uncovered charges**:
   - generations in the range whose charge exceeded their hold (ADR 0026): user email, completion day,
     hold, charge, uncovered amount;
   - users whose balance is below zero now: user email, balance.

   With none: "No uncovered charges."

### When Pixelforge's usage is unavailable

Pixelforge's cost and the difference show "Unavailable" on every day, with the note "Couldn't reach
Pixelforge's usage report. Our figures are shown alone." Nothing is highlighted. Every other figure is
shown as usual (ADR 0030).

## Flows

### Normal view

1. An operator opens the report. The range is the last 30 days.
2. The server sums our figures from the database, and asks the provider for its usage for the range
   (cached for 1 minute).
3. The page shows the totals, the days, the users and the uncovered charges; each day's difference
   is `0.000000000`.

### Long range

1. The operator picks a range of, say, 200 days.
2. The Pixelforge implementation requests usage in pieces of at most 92 days (92, 92, 16), joins the
   days, and returns them as one range. The report shows the 200 days as usual.

### Generation just completed

1. A generation completes at Pixelforge, and the cached usage, or our record, is a moment behind.
2. That day shows a non-zero difference, highlighted, next to "Pixelforge figures as of …".
3. Once the webhook is applied and the cache has expired, the difference returns to zero.

### Pixelforge unreachable

1. A usage request fails (overload, rate limit, network error), for any piece of the range.
2. The report shows our figures with the "Unavailable" column and note; nothing is highlighted.

### Making an operator

1. Someone with server access runs `php artisan user:make-admin reviewer@example.com`.
2. That user now sees **Operator report** in the sidebar and can open it.

## Acceptance criteria

Access

- [ ] `user:make-admin {email}` sets `is_admin` for that user; an unknown email prints an error and
      changes nothing.
- [ ] No web request can set `is_admin`.
- [ ] A non-operator gets `403` on the report, and does not see it in the sidebar; a guest is
      redirected to sign in.

Range

- [ ] Without dates, the report covers the last 30 UTC days ending today.
- [ ] A range longer than 92 days is accepted and shows every day.
- [ ] A start after the end is rejected with its message.
- [ ] Every day in the range has a row, with zeros on days with no generations.

Figures

- [ ] A generation counts on the UTC day of its provider `completed_at`, not the day it was created
      (a generation created at 23:59 UTC and completed at 00:01 UTC counts on the second day).
- [ ] Failed generations add nothing to any figure.
- [ ] User spend is the sum of charges, provider cost the sum of recorded provider costs, and profit
      their difference, on every row and in the totals.
- [ ] Per-user rows under a day add up to that day's row; per-user rows for the range add up to the
      totals.
- [ ] Every amount shows 9 decimals.

Pixelforge comparison

- [ ] Each day shows Pixelforge's `cost_usd` for that day and the difference from our provider cost;
      a non-zero difference is highlighted.
- [ ] For a range over 92 days, the provider is asked in consecutive pieces of at most 92 days, and
      no request covers more than 92 days.
- [ ] Usage is cached for the configured time (default 1 minute), and the page shows when it was
      fetched.
- [ ] When any usage request fails, the provider's column shows "Unavailable" with the note, nothing
      is highlighted, and all our figures are shown.
- [ ] Nothing outside `app/ImageProviders/Pixelforge/` knows the 92-day limit.
- [ ] On the deployed app, every day's difference is `0.000000000` once all generations are final.

Uncovered charges

- [ ] Generations in the range whose charge exceeds their hold are listed with user, day, hold,
      charge and uncovered amount, and the total appears in the totals.
- [ ] Users with a balance below zero are listed with their balance.

## Out of scope

- **Comparing token counts or generation counts with Pixelforge**: only the cost is compared; it is
  what the brief asks to match.
- **Exporting the report (CSV, PDF)**: not built.
- **Charts**: not built; the tables carry the numbers.
- **Removing an operator, or managing operators on a page**: not built; the command only grants.
- **Per-API-key or all-keys figures from Pixelforge**: the report compares with the app's own key
  only (`docs/spec/pixelforge-api.md#usage`).
- **Users' balances and activity in general**: only negative balances appear here; the rest is the
  `balances` spec.
