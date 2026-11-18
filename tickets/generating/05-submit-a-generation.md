# 05. Submit a generation

- Status: done
- Blocked by: 04
- Spec: docs/features/generating.md

## What to build

Pressing Generate validates the input, computes the estimate from the current pricing and, in one
transaction that locks the user's row, checks the balance, creates the generation in `pending` with
the rates the estimate used, inserts the hold and dispatches the submission job (the job itself
comes next). The user lands on a bare history page listing their own generations, newest first, with
"Generation submitted". Pricing failing at this moment creates nothing and puts the form in the
Price unavailable state.

## Acceptance criteria

- [x] A missing prompt, a prompt over 4,000 characters, an unknown size or quality, more than 4
      reference images, or a reference image that is not an http(s) URL is rejected with a message
      on that field, and creates no generation, hold or job.
- [x] A prompt of exactly 4,000 Unicode characters is accepted.
- [x] A submission whose maximum exceeds the balance at the server is refused with that message and
      creates nothing.
- [x] A user with no ledger rows cannot submit a generation.
- [x] A valid submission creates one generation in `pending` storing the rates the estimate used,
      one `hold` row of minus the estimate's maximum for the same input, and one queued submission
      job, all in one transaction.
- [x] Two concurrent submissions that together exceed the balance cannot both create a hold.
- [x] After submitting, the user is on the history page with "Generation submitted" and the new
      generation first.
- [x] When pricing fails at submission, no generation, hold or job is created, the form keeps its
      values, shows "We couldn't confirm the price, so nothing was submitted.", and enters the Price
      unavailable state.
- [x] The Generate button is disabled while the submission is in flight.

## Notes

- Decision: the concurrency criterion is proven by forking two processes against committed data in
  `tests/Concurrency/` (ADR 0039); the test fails with 2 holds when the row lock is removed.
- The generation stores its rates in four nanodollar columns and its estimate range, which the
  history cards will show.
- `NotEnoughCredits` lives in `app/Exceptions/`; its message is the one the form shows.
- "Generation submitted" is a `status` flash shown above the history list, as the history spec says.
- The submission job is dispatched but does nothing yet (ticket 06). The criterion "no web request
  calls the provider to create a generation" is proven in ticket 06, where the provider gains that
  call.
- Manual checks on the local app, 2026-11-18: Generate is disabled while the request is in flight;
  after submitting, History shows "Generation submitted" and the new `pending` generation first; an
  `ftp://` reference image shows "The reference image field must be a valid URL." under its row.
- Pricing failing at the moment of submission is proven by the feature test; the page then shows the
  message and enters the Price unavailable state through the same code as the estimate.
