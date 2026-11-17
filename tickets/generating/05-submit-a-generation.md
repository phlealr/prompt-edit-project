# 05. Submit a generation

- Status: todo
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

- [ ] A missing prompt, a prompt over 4,000 characters, an unknown size or quality, more than 4
      reference images, or a reference image that is not an http(s) URL is rejected with a message
      on that field, and creates no generation, hold or job.
- [ ] A prompt of exactly 4,000 Unicode characters is accepted.
- [ ] A submission whose maximum exceeds the balance at the server is refused with that message and
      creates nothing.
- [ ] A user with no ledger rows cannot submit a generation.
- [ ] A valid submission creates one generation in `pending` storing the rates the estimate used,
      one `hold` row of minus the estimate's maximum for the same input, and one queued submission
      job, all in one transaction.
- [ ] Two concurrent submissions that together exceed the balance cannot both create a hold.
- [ ] After submitting, the user is on the history page with "Generation submitted" and the new
      generation first.
- [ ] When pricing fails at submission, no generation, hold or job is created, the form keeps its
      values, shows "We couldn't confirm the price, so nothing was submitted.", and enters the Price
      unavailable state.
- [ ] The Generate button is disabled while the submission is in flight.

## Notes
