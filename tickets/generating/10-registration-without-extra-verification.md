# 10. Registration without extra verification

- Status: done
- Blocked by: none
- Spec: docs/features/generating.md

## What to build

Fortify's enabled features are registration and password reset only (ADR 0017). Email verification,
two-factor authentication and passkeys are turned off, and the screens, routes and tests that served
them are removed, so a new user signs up and lands on Generate with nothing in between.

## Acceptance criteria

- [x] Fortify enables only registration and password reset.
- [x] A user who registers lands on Generate, with no email verification step.
- [x] No page, route or component offers two-factor authentication or passkeys.
- [x] Signing in, signing out, password reset and the password update in settings still work.

## Notes

- Fortify enables registration and password reset only. `User` no longer implements
  `MustVerifyEmail` or uses the two-factor and passkey traits, so registering sends no verification
  email. The verification, two-factor and passkey pages, components, routes, rate limiters, factory
  states, types and their starter kit tests are removed; the settings routes no longer use `verified`.
- Their database columns and migrations stay: harmless, and part of the starter kit's history.
- Follow-up, optional: the `@laravel/passkeys` and `vue-input-otp` packages and the input-otp UI
  components are still installed but unused.
- Manual checks on the local app, 2026-11-18: registering landed on Generate; the confirm-password
  and Security settings pages mention no passkey or two-factor; Security shows only "Update password".
