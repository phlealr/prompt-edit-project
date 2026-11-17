# 0017. Anyone can register, with no email verification, no 2FA and no welcome credits

- Status: accepted
- Date: 2026-11-17

## Context

The brief only requires that users sign in (`docs/spec/assessment.md#what-to-build`); it does not
mention registration, email verification, 2FA or starting credits. The reviewers will use the deployed
app to look around (`docs/spec/assessment.md#what-to-send-us`). "Buy credits" is a free, instant fake
purchase (`docs/spec/assessment.md#stretch-goals-bonus-points`). The starter kit's Fortify can enable
registration, password reset, email verification and 2FA.

## Decision

Registration is open. Password reset stays enabled. Email verification and two-factor authentication
are disabled. New users start with no credits; when the balance does not cover a generation, the
generate page clearly invites them to buy credits.

## Rules it creates

- Fortify's enabled features are registration and password reset only.
- A new user's ledger is empty.
- When the balance does not cover the estimate's maximum, the generate page blocks submission and
  shows a call to buy credits.

## Consequences

Reviewers can create their own accounts in seconds, and the first thing they meet is the balances
feature. The app has no protection against throwaway accounts, which is acceptable because credits
are fake.

## Alternatives considered

- **Email verification and 2FA**: friction for the reviewers with no grading value.
- **Welcome credits**: redundant with a free "Buy credits" button, and would hide the balances
  feature.

## In plain words

Anyone can sign up straight away, with no email check or 2FA, because the brief asks for neither and
reviewers need to get in fast. New users start at zero and are pointed to "Buy credits", which shows
off the balances feature.
