# Balances

- Status: approved
- Sources:
  - Brief: [Stretch goals](../spec/assessment.md#stretch-goals-bonus-points) (Balances),
    [What we look at](../spec/assessment.md#what-we-look-at)
  - ADRs: 0001, 0005, 0006, 0007, 0017, 0020, 0026, 0028, 0029
  - Specs: [generating](generating.md), [generation-updates](generation-updates.md),
    [history](history.md)

## Goal

A user always knows their balance, can buy credits with a fake purchase, and can see where every cent
went. Generating is blocked when they cannot afford it (the `generating` spec). This meets the brief's
**Balances** stretch goal, and is built before the first deploy, since new users start with no credits
(ADR 0001, ADR 0017).

## Behaviour

### The balance in the sidebar

Every signed-in page shows the balance in the sidebar, in the 4-decimal format with the exact amount on
hover (ADR 0007), linking to the Credits page. It is current on every page load, and on every History
reload (ADR 0028), so a charge shows up without the user doing anything. A balance below zero shows in
the error colour.

### The buy form

Used on the Credits page, and on Generate in the "Not enough credits" state (the `generating` spec).

- An **Amount** field, in whole dollars, empty at first.
- Quick buttons **$5**, **$10** and **$25** that fill the field; the user can change it after.
- A **Buy credits** button that buys the amount in the field, disabled while its request is in flight.
- A purchase is a whole number of dollars from 1 to 1,000 (ADR 0029). Anything else shows "Enter a
  whole number of dollars between 1 and 1,000." next to the field and buys nothing.
- After a purchase: the message "Added $10.0000 to your balance", the field empties, and the user stays on
  the page they were on. On Generate, the prompt and options they typed are kept.

### The Credits page

In the sidebar as **Credits**. It shows, top to bottom:

1. **Balance**: the balance. When generations are still running: "$0.4673 of it is held for
   generations still running. It's charged or given back when they finish." When the balance is below
   zero: "Your balance is below zero because a generation cost more than its estimate. Buy credits to
   generate again."
2. **Buy credits**: the buy form.
3. **Activity**: the user's purchases and charges, newest first, 20 per page with previous and next
   links:
   - a purchase: "Bought credits", `+$10.0000`;
   - a charge: "Generation" and the prompt cut to one line, `−$0.0345`, linking to History;
   - each with its date and time, in the user's local time.

   Holds and releases are not listed; together they only show as the held amount. With no activity:
   "No activity yet. Buy credits to start generating."

### Where the numbers come from

- The **balance** is the sum of all the user's ledger rows (ADR 0006): purchases, holds, releases and
  charges. A running generation's hold is already taken out of it.
- The **held amount** is the sum of the holds of the user's generations that have no release yet,
  shown as a positive amount.
- The activity's purchases and charges, plus the held amount, add up to the balance.

## Flows

### First purchase

1. A new user signs in and lands on Generate. The balance is `$0.0000`; the form is in the "Not enough
   credits" state with the buy form.
2. They click **$10**: the field shows `10`. They click **Buy credits**.
3. One `purchase` row of 10,000,000,000 nanodollars is inserted. The page stays, keeps their prompt,
   and shows "Added $10.0000 to your balance". The sidebar shows `$10.0000`, and Generate is enabled.

### Changing the amount

1. The user clicks **$25**, then edits the field to `40`, and buys. One `purchase` of $40 is inserted.
2. They enter `10.50`, `0`, `-5` or `1001` and press Buy credits: the field shows the error, nothing is
   inserted.

### Balance through a generation

1. Balance $10.0000. The user submits a generation whose estimate's maximum is $0.4673. The hold is
   inserted: the balance shows $9.5327, and Credits shows "$0.4673 of it is held…".
2. The generation succeeds and costs $0.0345. The `release` and the `charge` are inserted
   (`generation-updates` spec): the balance shows $9.9655, the held line disappears, and the activity
   gains "Generation … −$0.0345".
3. If it fails instead, only the `release` is inserted: the balance is back to $10.0000 and no activity
   line is added.

### Balance below zero

1. A generation's charge exceeds its hold (ADR 0026) and the balance drops below zero.
2. The sidebar shows the balance in the error colour; Credits explains why; Generate is blocked until a
   purchase brings the balance up to the next hold.

### Double click

The Buy credits button is disabled from the first click until the server answers, so one click buys
once.

## Acceptance criteria

The balance

- [ ] Every signed-in page shows the balance in the sidebar, with 4 decimals and the exact amount on
      hover, linking to Credits.
- [ ] The sidebar balance updates on a History reload when a generation is charged or released.
- [ ] A new user's balance is $0.0000 and their ledger is empty.
- [ ] The balance equals the sum of the user's ledger rows, and no column stores it.
- [ ] A negative balance shows in the error colour, and Credits shows the "below zero" message.

Buying

- [ ] The quick buttons fill the amount field with 5, 10 or 25 and buy nothing on their own.
- [ ] Buying 10 inserts exactly one `purchase` row of 10,000,000,000 nanodollars for that user and
      shows "Added $10.0000 to your balance".
- [ ] Amounts 1 and 1,000 are accepted; 0, −5, 10.50, 1,001 and an empty field are rejected on the
      server with the field's message, and insert nothing.
- [ ] The Buy credits button is disabled while its request is in flight.
- [ ] Buying on Generate keeps the prompt and options, updates the balance, and enables Generate when
      the balance now covers the estimate's maximum.
- [ ] Guests cannot buy credits.

The Credits page

- [ ] Credits shows the balance, the buy form and the activity.
- [ ] The activity lists purchases and charges only, newest first, 20 per page, each with its signed
      amount, date and time; a charge shows its generation's prompt and links to History.
- [ ] While a generation is running, Credits shows its hold as the held amount; once it is final, the
      held amount no longer includes it.
- [ ] The activity's purchases and charges, plus the held amount, add up to the balance shown.
- [ ] A user never sees another user's balance or activity.
- [ ] With no activity, the page shows "No activity yet. Buy credits to start generating."

## Out of scope

- **Real payments, refunds, invoices**: the purchase is fake (brief).
- **A maximum total balance**: none, since the money is fake; each purchase is capped instead
  (ADR 0029).
- **Giving credits back or adjusting balances by hand**: not built.
- **Listing holds and releases as activity lines**: they show only as the held amount.
- **Operators viewing other users' balances**: the `operator-report` spec.
