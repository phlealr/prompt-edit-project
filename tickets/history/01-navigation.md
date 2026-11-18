# 01. Navigation

- Status: done
- Blocked by: generating 03
- Spec: docs/features/history.md

## What to build

The starter kit's Dashboard goes away. Signing in, or opening the app's home while signed in, lands
on Generate, and the sidebar shows Generate and History. Guests are sent to sign in.

## Acceptance criteria

- [x] A guest opening History is redirected to sign in.
- [x] The Dashboard page and its route no longer exist; signing in lands on Generate.
- [x] The sidebar has Generate and History.

## Notes

- Fortify's `home` is `/generate`; `/` is guest-only, and `redirectUsersTo('/generate')` sends a
  signed-in user there (without it, the guest-only home would redirect to itself).
- The starter kit's Auth tests now expect Generate instead of the removed Dashboard, and one test
  method was renamed to say so; the passkey sign-in fallback also points to Generate.
- Manual check on the local app, 2026-11-18: signing in lands on Generate; the sidebar shows
  Generate and History (plus the starter kit's footer links).
