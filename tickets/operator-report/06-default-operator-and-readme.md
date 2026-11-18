# 06. Default operator and README

- Status: done
- Blocked by: 01
- Spec: docs/features/operator-report.md

## What to build

A default operator account, created from `OPERATOR_*` environment variables whose defaults are the
login published in the README, so reviewers can open the operator report. A README tells reviewers
what the app is, how to sign in and use it, and how to run it locally.

## Acceptance criteria

- [x] `php artisan db:seed --class=OperatorSeeder --force` creates the operator from
      `OPERATOR_EMAIL`, `OPERATOR_NAME` and `OPERATOR_PASSWORD`, with defaults matching the README.
- [x] Running it again creates nothing new and keeps the account an operator.
- [x] Nobody can change the shared operator's name, email or password from the web, including by
      resetting its password.
- [x] The README gives the deployed URL, the operator login, how to use the app, the test keywords,
      and how to run and check it locally.

## Notes

- Decision (user, 2026-11-18): the operator's password is published in the public README.
